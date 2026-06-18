<?php
include "../../config/database.php";

if (isset($_POST['simpan'])) {
    mysqli_query($conn,"INSERT INTO prosedur
    (jenis,judul,deskripsi)
    VALUES (
        '$_POST[jenis]',
        '$_POST[judul]',
        '$_POST[deskripsi]'
    )");
    header("Location: prosedur.php");
}

if (isset($_GET['hapus'])) {
    mysqli_query($conn,"DELETE FROM prosedur WHERE id_prosedur='$_GET[hapus]'");
    header("Location: prosedur.php");
}
?>

<h2>Data Prosedur Akademik</h2>

<form method="post">
<select name="jenis" required>
  <option value="">- Pilih Jenis -</option>
  <option value="KP">Kerja Praktik</option>
  <option value="TA">Tugas Akhir</option>
  <option value="FRS">FRS</option>
</select>

<input type="text" name="judul" placeholder="Judul Prosedur" required>

<textarea name="deskripsi" placeholder="Deskripsi Prosedur" required></textarea>

<button name="simpan">Simpan</button>
</form>

<hr>

<table border="1">
<tr>
<th>No</th><th>Jenis</th><th>Judul</th><th>Deskripsi</th><th>Aksi</th>
</tr>

<?php
$no=1;
$q=mysqli_query($conn,"SELECT * FROM prosedur ORDER BY jenis");
while($r=mysqli_fetch_assoc($q)){
?>
<tr>
<td><?= $no++ ?></td>
<td><?= $r['jenis'] ?></td>
<td><?= $r['judul'] ?></td>
<td><?= $r['deskripsi'] ?></td>
<td>
<a href="?hapus=<?= $r['id_prosedur'] ?>" onclick="return confirm('Hapus?')">Hapus</a>
</td>
</tr>
<?php } ?>
</table>
<script src="../../assets/js/admin-delete-confirm.js"></script>
