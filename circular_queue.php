<?php
session_start();

// Inisialisasi Circular Queue
if (!isset($_SESSION['queue_pasien_circular'])) {
    $_SESSION['queue_pasien_circular'] = [];
    $_SESSION['front_pasien_circular'] = -1; // Indeks elemen pertama
    $_SESSION['rear_pasien_circular'] = -1; // Indeks elemen terakhir
    $_SESSION['max_size_circular'] = 5; // Ukuran maksimum Circular Queue
}
if (!isset($_SESSION['queue_apotek_circular'])) {
    $_SESSION['queue_apotek_circular'] = [];
}

// Fungsi untuk mengecek apakah antrean penuh
function isFull($queue)
{
    global $_SESSION;
    return ($_SESSION["front_{$queue}_circular"] == 0 && $_SESSION["rear_{$queue}_circular"] == $_SESSION['max_size_circular'] - 1) ||
        ($_SESSION["rear_{$queue}_circular"] + 1 == $_SESSION["front_{$queue}_circular"]);
}

// Fungsi untuk mengecek apakah antrean kosong
function isEmpty($queue)
{
    global $_SESSION;
    return $_SESSION["front_{$queue}_circular"] == -1;
}

// Fungsi untuk menambahkan elemen ke Circular Queue
function enqueue($queue, $data)
{
    global $_SESSION;
    if (isFull($queue)) {
        return "Antrean $queue penuh!";
    }

    if ($_SESSION["front_{$queue}_circular"] == -1) {
        $_SESSION["front_{$queue}_circular"] = 0;
    }
    $_SESSION["rear_{$queue}_circular"] = ($_SESSION["rear_{$queue}_circular"] + 1) % $_SESSION['max_size_circular'];
    $_SESSION["queue_{$queue}_circular"][$_SESSION["rear_{$queue}_circular"]] = $data;

    return "Berhasil menambahkan ke antrean $queue.";
}

// Fungsi untuk menghapus elemen dari Circular Queue
function dequeue($queue)
{
    global $_SESSION;
    if (isEmpty($queue)) {
        return null;
    }

    $data = $_SESSION["queue_{$queue}_circular"][$_SESSION["front_{$queue}_circular"]];
    if ($_SESSION["front_{$queue}_circular"] == $_SESSION["rear_{$queue}_circular"]) {
        $_SESSION["front_{$queue}_circular"] = -1;
        $_SESSION["rear_{$queue}_circular"] = -1;
    } else {
        $_SESSION["front_{$queue}_circular"] = ($_SESSION["front_{$queue}_circular"] + 1) % $_SESSION['max_size_circular'];
    }

    return $data;
}

$error_message = '';
// Tambah pasien ke antrean pasien
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient_circular'])) {
    if (isFull('pasien')) {
        $error_message = "Antrean pasien sudah penuh! Tidak bisa menambahkan data baru.";
    } else {
        $new_patient = [
            'no_peserta' => uniqid(),
            'nama' => $_POST['nama'],
            'keluhan' => $_POST['keluhan']
        ];
        $message = enqueue('pasien', $new_patient);
    }
}

// Pindahkan pasien dari antrean pasien ke apotek
if (isset($_GET['move_to_apotek_circular'])) {
    $patient = dequeue('pasien');
    if ($patient) {
        $patient['resep_obat'] = $_POST['resep_obat'] ?? '';
        $_SESSION['queue_apotek_circular'][] = $patient;
    }
    header('Location: circular_queue.php');
    exit();
}

// Hapus pasien dari antrean apotek
if (isset($_GET['delete_from_apotek_circular'])) {
    $index = (int) $_GET['delete_from_apotek_circular'];
    if (isset($_SESSION['queue_apotek_circular'][$index])) {
        array_splice($_SESSION['queue_apotek_circular'], $index, 1);
    }
    header('Location: circular_queue.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antrean Circular Queue</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="container my-5">
        <h1 class="mb-4">Antrean Rumah Sakit (Circular Queue)</h1>

        <!-- Form Tambah Pasien -->
        <form method="POST" class="mb-4">
            <div class="mb-3">
                <label for="nama" class="form-label">Nama Pasien</label>
                <input type="text" name="nama" id="nama" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="keluhan" class="form-label">Keluhan</label>
                <input type="text" name="keluhan" id="keluhan" class="form-control" required>
            </div>
            <button type="submit" name="add_patient_circular" class="btn btn-primary">Tambah Pasien</button>
        </form>
        <!-- Pesan Error -->
        <?php if ($error_message): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        <!-- Antrean Pasien -->
        <h2>Antrean Pasien</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>No Peserta</th>
                    <th>Nama</th>
                    <th>Keluhan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!isEmpty('pasien')): ?>
                    <?php
                    $index = $_SESSION['front_pasien_circular'];
                    do {
                        $pasien = $_SESSION['queue_pasien_circular'][$index];
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($pasien['no_peserta']) ?></td>
                            <td><?= htmlspecialchars($pasien['nama']) ?></td>
                            <td><?= htmlspecialchars($pasien['keluhan']) ?></td>
                            <td>
                                <button type="button" class="btn btn-success btn-sm btn-move-to-apotek"
                                    data-bs-target="#modalResep<?= $index ?>" data-bs-toggle="modal">
                                    Pindah ke Apotek
                                </button>
                                <div class="modal fade" id="modalResep<?= $index ?>" tabindex="-1"
                                    aria-labelledby="inputResepModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" id="moveToApotekForm"
                                                action="?move_to_apotek_circular=<?php $index ?>">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="inputResepModalLabel">Input Resep Obat</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="move_to_apotek_circular" value="1">
                                                    <div class="mb-3">
                                                        <label for="resep_obat" class="form-label">Resep Obat</label>
                                                        <textarea name="resep_obat" id="resep_obat" class="form-control"
                                                            rows="3" required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">Simpan dan Pindah</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php
                        $index = ($index + 1) % $_SESSION['max_size_circular'];
                    } while ($index != ($_SESSION['rear_pasien_circular'] + 1) % $_SESSION['max_size_circular']);
                    ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">Antrean kosong</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Antrean Apotek -->
        <h2>Antrean Apotek</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>No Peserta</th>
                    <th>Nama</th>
                    <th>Keluhan</th>
                    <th>Resep Obat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($_SESSION['queue_apotek_circular'] as $index => $apotek): ?>
                    <tr>
                        <td><?= htmlspecialchars($apotek['no_peserta']) ?></td>
                        <td><?= htmlspecialchars($apotek['nama']) ?></td>
                        <td><?= htmlspecialchars($apotek['keluhan']) ?></td>
                        <td><?= htmlspecialchars($apotek['resep_obat']) ?></td>
                        <td>
                            <a href="?delete_from_apotek_circular=<?= $index ?>" class="btn btn-danger btn-sm">Selesai</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>


</html>