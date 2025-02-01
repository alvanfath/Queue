<?php
session_start();

// Inisialisasi Double-ended Queue (Deque)
if (!isset($_SESSION['deque_pasien'])) {
    $_SESSION['deque_pasien'] = [];
}
if (!isset($_SESSION['deque_apotek'])) {
    $_SESSION['deque_apotek'] = [];
}

// Fungsi untuk menambahkan elemen ke Deque (depan atau belakang)
function enqueueDeque(&$deque, $data, $isFront = false)
{
    if ($isFront) {
        array_unshift($deque, $data); // Tambah ke depan
    } else {
        array_push($deque, $data); // Tambah ke belakang
    }
}

// Fungsi untuk menghapus elemen dari Deque (depan atau belakang)
function dequeueDeque(&$deque, $isFront = true)
{
    if (empty($deque)) {
        return null;
    }
    return $isFront ? array_shift($deque) : array_pop($deque);
}

$error_message = '';

// Tambah pasien ke antrean pasien
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient_deque'])) {
    $new_patient = [
        'no_peserta' => uniqid(),
        'nama' => $_POST['nama'],
        'keluhan' => $_POST['keluhan']
    ];
    enqueueDeque($_SESSION['deque_pasien'], $new_patient); // Tambah ke belakang
}

// Pindahkan pasien dari antrean pasien ke apotek
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['move_to_apotek_deque'])) {
    $patient = dequeueDeque($_SESSION['deque_pasien']); // Ambil dari depan
    if ($patient) {
        $patient['resep_obat'] = $_POST['resep_obat'] ?? '';
        enqueueDeque($_SESSION['deque_apotek'], $patient); // Tambah ke belakang
    }
    header('Location: deque_queue.php');
    exit();
}

// Hapus pasien dari antrean apotek (depan atau belakang)
if (isset($_GET['delete_from_apotek_deque'])) {
    $isFront = ($_GET['delete_from_apotek_deque'] === 'front');
    dequeueDeque($_SESSION['deque_apotek'], $isFront);
    header('Location: deque_queue.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antrean Rumah Sakit - Double-ended Queue</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="container my-5">
        <h1 class="mb-4">Antrean Rumah Sakit (Double-ended Queue)</h1>

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
            <button type="submit" name="add_patient_deque" class="btn btn-primary">Tambah Pasien</button>
        </form>

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
                <?php foreach ($_SESSION['deque_pasien'] as $index => $pasien): ?>
                    <tr>
                        <td><?= htmlspecialchars($pasien['no_peserta']) ?></td>
                        <td><?= htmlspecialchars($pasien['nama']) ?></td>
                        <td><?= htmlspecialchars($pasien['keluhan']) ?></td>
                        <td>
                            <?php if($index == 0): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="move_to_apotek_deque" value="1">
                                <input type="hidden" name="resep_obat" value="Resep dari Dokter">
                                <button type="submit" class="btn btn-success btn-sm">Pindah ke Apotek</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Antrean Apotek -->
        <h2>Antrean Apotek</h2>
        <!-- Tombol Hapus Depan dan Hapus Belakang -->
        <form method="GET" class="mb-3">
            <button type="submit" name="delete_from_apotek_deque" value="front" class="btn btn-danger btn-sm">Hapus
                Depan</button>
            <button type="submit" name="delete_from_apotek_deque" value="rear" class="btn btn-warning btn-sm">Hapus
                Belakang</button>
        </form>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>No Peserta</th>
                    <th>Nama</th>
                    <th>Keluhan</th>
                    <th>Resep Obat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($_SESSION['deque_apotek'] as $index => $apotek): ?>
                    <tr>
                        <td><?= htmlspecialchars($apotek['no_peserta']) ?></td>
                        <td><?= htmlspecialchars($apotek['nama']) ?></td>
                        <td><?= htmlspecialchars($apotek['keluhan']) ?></td>
                        <td><?= htmlspecialchars($apotek['resep_obat']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>