<?php
$conn = mysqli_connect("127.0.0.1", "root", "", "dinda", 3307);
if (!$conn) die("Koneksi gagal");

// TOGGLE KONTROL
if (isset($_GET['toggle'])) {
    $kolom = $_GET['toggle'];

    $data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM rumah_pintar WHERE id=1"));
    $nilai = $data[$kolom] == 1 ? 0 : 1;

    mysqli_query($conn, "UPDATE rumah_pintar SET $kolom=$nilai WHERE id=1");
    exit;
}

// DATA SENSOR (UNTUK GRAFIK)
$q = mysqli_query($conn, "SELECT * FROM monitoring_sensor");
$sensor = [];
while ($d = mysqli_fetch_assoc($q)) $sensor[] = $d;

// DATA TERBARU (UNTUK ASAP & GAS)
$latest = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM monitoring_sensor ORDER BY id DESC LIMIT 1"));

// DATA KONTROL
$kontrol = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM rumah_pintar WHERE id=1"));
?>

<!DOCTYPE html>
<html>
<head>
<title>IoT Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body { font-family: Arial; margin:0; background:#f4f6f9; }

nav {
    background:#2c3e50;
    padding:15px;
}
nav a {
    color:white;
    margin-right:20px;
    text-decoration:none;
}

.container { padding:20px; }

.card {
    background:white;
    padding:20px;
    border-radius:10px;
    margin-bottom:20px;
    box-shadow:0 2px 5px rgba(0,0,0,0.1);
}

/* TOGGLE SWITCH */
.switch {
  position: relative;
  display: inline-block;
  width: 60px;
  height: 34px;
}
.switch input { display:none; }

.slider {
  position: absolute;
  cursor: pointer;
  background-color: #ccc;
  transition: .4s;
  border-radius: 34px;
  top:0; left:0; right:0; bottom:0;
}
.slider:before {
  position: absolute;
  content: "";
  height: 26px;
  width: 26px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  transition: .4s;
  border-radius: 50%;
}
input:checked + .slider {
  background-color: #28a745;
}
input:checked + .slider:before {
  transform: translateX(26px);
}

.item {
    display:flex;
    justify-content:space-between;
    margin-bottom:10px;
    padding:10px;
    border-bottom:1px solid #eee;
}

/* STATUS BOX */
.status-box {
    padding:15px;
    border-radius:10px;
    text-align:center;
    font-size:18px;
    font-weight:bold;
    margin-bottom:10px;
}
.safe { background:#d4edda; color:#155724; }
.danger { background:#f8d7da; color:#721c24; }

</style>
</head>

<body>

<nav>
    <a href="?page=monitoring">Monitoring</a>
    <a href="?page=kontrol">Kontrol</a>
</nav>

<div class="container">

<?php if (!isset($_GET['page']) || $_GET['page']=="monitoring") { ?>

<!-- ================= MONITORING ================= -->

<div class="card">
<h3>Grafik Suhu</h3>
<canvas id="suhu"></canvas>
</div>

<div class="card">
<h3>Grafik Kelembapan</h3>
<canvas id="kelembapan"></canvas>
</div>

<div class="card">
<h3>Grafik Cahaya</h3>
<canvas id="cahaya"></canvas>
</div>

<div class="card">
<h3>Status Sensor (Realtime)</h3>

<div class="status-box <?= $latest['asap'] ? 'danger':'safe' ?>">
    Asap: <?= $latest['asap'] ? 'TERDETEKSI 🔥' : 'AMAN ✅' ?>
</div>

<div class="status-box <?= $latest['gas'] ? 'danger':'safe' ?>">
    Gas: <?= $latest['gas'] ? 'TERDETEKSI ⚠️' : 'AMAN ✅' ?>
</div>

</div>

<script>
const data = <?php echo json_encode($sensor); ?>;

function buatChart(id, label, field) {
    new Chart(document.getElementById(id), {
        type:'line',
        data:{
            labels:data.map(d=>d.id),
            datasets:[{
                label:label,
                data:data.map(d=>d[field]),
                tension:0.3
            }]
        }
    });
}

buatChart('suhu','Suhu','suhu');
buatChart('kelembapan','Kelembapan','kelembapan');
buatChart('cahaya','Cahaya','cahaya');

// AUTO REFRESH
setInterval(()=>location.reload(),5000);
</script>

<?php } else { ?>

<!-- ================= KONTROL ================= -->

<div class="card">
<h3>Kontrol Rumah Pintar</h3>

<?php
function toggleUI($nama, $val){
?>
<div class="item">
<span><?= $nama ?></span>
<label class="switch">
<input type="checkbox" <?= $val?'checked':'' ?> onchange="toggle('<?= $nama ?>')">
<span class="slider"></span>
</label>
</div>
<?php } ?>

<?php
toggleUI("lampu_halaman", $kontrol['lampu_halaman']);
toggleUI("lampu_taman", $kontrol['lampu_taman']);
toggleUI("lampu_ruangan", $kontrol['lampu_ruangan']);
toggleUI("gerbang_garasi", $kontrol['gerbang_garasi']);
toggleUI("pakan_kucing", $kontrol['pakan_kucing']);
toggleUI("pakan_ikan", $kontrol['pakan_ikan']);
?>

</div>

<script>
function toggle(kolom){
    fetch("?toggle="+kolom)
}

// AUTO SYNC
setInterval(()=>location.reload(),3000);
</script>

<?php } ?>

</div>

</body>
</html>