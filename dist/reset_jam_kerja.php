<?php
include 'koneksi.php';
mysqli_query($conn, "TRUNCATE jam_kerja");
mysqli_query($conn, "INSERT INTO `jam_kerja` (`kode`,`nama_jam`,`jam_mulai`,`jam_selesai`,`warna`) VALUES
  ('PAGI','Pagi','09:00:00','14:00:00','#3B82F6'),
  ('SIANG','Siang','14:00:00','21:00:00','#10B981'),
  ('MALAM','Malam','21:00:00','09:00:00','#8B5CF6'),
  ('LEPAS','Lepas Malam','00:00:00','00:00:00','#64748B'),
  ('LIBUR','Libur','00:00:00','00:00:00','#94A3B8')");
echo "Database jam_kerja reset to 5-day cycle standards.";
?>
