<?php
session_start();

// Inisialisasi antrean pasien dan apotek
if (!isset($_SESSION['queue_pasien_priority'])) {
    $_SESSION['queue_pasien_priority'] = [];
}
if (!isset($_SESSION['queue_apotek'])) {
    $_SESSION['queue_apotek'] = [];
}

// Fungsi untuk menambahkan pasien ke antrean pasien
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient_priority'])) {
    $new_patient = [
        'no_peserta' => uniqid(),
        'nama' => $_POST['nama'],
        'keluhan' => $_POST['keluhan'],
        'prioritas' => (int) $_POST['prioritas'], // Tambahkan prioritas
    ];
    $_SESSION['queue_pasien_priority'][] = $new_patient;

    // Sortir antrean berdasarkan prioritas
    usort($_SESSION['queue_pasien_priority'], function ($a, $b) {
        return $a['prioritas'] - $b['prioritas']; // Prioritas lebih kecil = lebih tinggi
    });

    header('Location: priority_queue.php');
    exit();
}

// Fungsi untuk memindahkan pasien dari antrean pasien ke apotek
if (isset($_GET['move_to_apotek_priority'])) {
    $index = (int) $_GET['move_to_apotek_priority'];
    if (isset($_SESSION['queue_pasien_priority'][$index])) {
        $pasien = $_SESSION['queue_pasien_priority'][$index];
        $pasien['resep_obat'] = $_POST['resep_obat'] ?? ''; // Tambahkan resep obat
        $_SESSION['queue_apotek'][] = $pasien;
        array_splice($_SESSION['queue_pasien_priority'], $index, 1);
    }
    header('Location: priority_queue.php');
    exit();
}

// Fungsi untuk menghapus pasien dari antrean apotek
if (isset($_GET['delete_from_apotek_priority'])) {
    $index = (int) $_GET['delete_from_apotek_priority'];
    if (isset($_SESSION['queue_apotek'][$index])) {
        array_splice($_SESSION['queue_apotek'], $index, 1);
    }
    header('Location: priority_queue.php');
    exit();
}

// Fungsi untuk menghapus pasien dari antrean pasien
if (isset($_GET['delete_from_pasien_priority'])) {
    $index = (int) $_GET['delete_from_pasien_priority'];
    if (isset($_SESSION['queue_pasien_priority'][$index])) {
        array_splice($_SESSION['queue_pasien_priority'], $index, 1);
    }
    header('Location: priority_queue.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antrean Rumah Sakit dengan Priority Queue</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="container my-5">
        <h1 class="mb-4">Antrean Rumah Sakit (Priority Queue)</h1>

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
            <div class="mb-3">
                <label for="prioritas" class="form-label">Prioritas (Angka, semakin kecil semakin tinggi)</label>
                <input type="number" name="prioritas" id="prioritas" class="form-control" required>
            </div>
            <button type="submit" name="add_patient_priority" class="btn btn-primary">Tambah Pasien</button>
        </form>

        <!-- Antrean Pasien -->
        <h2>Antrean Pasien</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>No Peserta</th>
                    <th>Nama</th>
                    <th>Keluhan</th>
                    <th>Prioritas</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($_SESSION['queue_pasien_priority'] as $index => $pasien): ?>
                    <tr>
                        <td><?= htmlspecialchars($pasien['no_peserta']) ?></td>
                        <td><?= htmlspecialchars($pasien['nama']) ?></td>
                        <td><?= htmlspecialchars($pasien['keluhan']) ?></td>
                        <td><?= htmlspecialchars($pasien['prioritas']) ?></td>

                        <td>
                            <!-- Button pindahkan ke apotek -->
                            <?php if ($index == 0) { ?>
                                <button class="btn btn-success btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#modalResep<?= $index ?>">Pindah ke Apotek</button>

                                <!-- Modal untuk input resep obat -->
                                <div class="modal fade" id="modalResep<?= $index ?>" tabindex="-1"
                                    aria-labelledby="modalResepLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form method="POST" action="?move_to_apotek_priority=<?= $index ?>">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="modalResepLabel">Resep Obat</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label for="resep_obat_<?= $index ?>" class="form-label">Resep
                                                            Obat</label>
                                                        <input type="text" name="resep_obat" id="resep_obat_<?= $index ?>"
                                                            class="form-control" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">Pindahkan</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php } ?>

                            <!-- Button hapus -->
                            <a href="?delete_from_pasien_priority=<?= $index ?>" class="btn btn-danger btn-sm">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
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
                <?php foreach ($_SESSION['queue_apotek'] as $index => $apotek): ?>
                    <tr>
                        <td><?= htmlspecialchars($apotek['no_peserta']) ?></td>
                        <td><?= htmlspecialchars($apotek['nama']) ?></td>
                        <td><?= htmlspecialchars($apotek['keluhan']) ?></td>
                        <td><?= htmlspecialchars($apotek['resep_obat']) ?></td>
                        <td>
                            <a href="?delete_from_apotek_priority=<?= $index ?>" class="btn btn-danger btn-sm">Selesai</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>