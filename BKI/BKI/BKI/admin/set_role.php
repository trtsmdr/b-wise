<?php
    session_start();
    include("koneksi.php");

    date_default_timezone_set('Asia/Jakarta');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $role = $_POST['role'];
        $latitude  = isset($_POST['latitude']) ? $_POST['latitude'] : null;
        $longitude = isset($_POST['longitude']) ? $_POST['longitude'] : null;
        $_SESSION['role'] = $role;

        if ($role === 'User') {
            $user_id = $_SESSION['user_id'];
            $tanggal = date('Y-m-d');
            $time_login = date('H:i:s');
        
            $check_query  = "SELECT * FROM time WHERE user_id = $user_id AND tanggal = '$tanggal'";
            $check_result = mysqli_query($koneksi, $check_query);
        
            if (mysqli_num_rows($check_result) > 0) {
                $row = mysqli_fetch_assoc($check_result);
                if ($row['is_break'] == true && empty($row['after_break'])) {
                    $geotagging = $latitude . ',' . $longitude;
                    $update_query = "UPDATE time SET after_break = '$time_login', geotagging_after_break = '$geotagging', is_break = false WHERE user_id = $user_id AND tanggal = '$tanggal'";
                    if (mysqli_query($koneksi, $update_query)) {
                        echo "<!DOCTYPE html>
                                <html lang='en'>
                                <head>
                                    <meta charset='UTF-8'>
                                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                                    <title>Welcome Back</title>
                                    <link href='img/logo.png' rel='icon'>
                                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                                </head>
                                <body>
                                    <script>
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Happy return to activity!',
                                            text: 'You are back from the break.',
                                            backdrop: true,
                                            confirmButtonColor: '#3085d6',
                                            confirmButtonText: 'OK'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                window.location.href = 'dashboard.php';
                                            }
                                        });
                                    </script>
                                </body>
                                </html>";
                        exit;
                    }
                }
        
                if ($row['before_break'] == '00:00:00' || empty($row['before_break'])) {
                    $batas_waktu = strtotime('08:00:00');
                    $waktu_login = strtotime($time_login);
        
                    if ($waktu_login > $batas_waktu) {
                        $_SESSION['telat'] = true;
                        $selisih     = $waktu_login - $batas_waktu;
                        $menit_telat = floor($selisih / 60);
                        $jam_telat   = floor($menit_telat / 60);
                        $sisa_menit_telat = $menit_telat % 60;
                        $detik_telat = $selisih % 60;
        
                        if ($jam_telat > 0) {
                            $_SESSION['telat_waktu'] = "$jam_telat hours $sisa_menit_telat minutes $detik_telat seconds late!";
                        } elseif ($sisa_menit_telat > 0) {
                            $_SESSION['telat_waktu'] = "$sisa_menit_telat minutes $detik_telat seconds late!";
                        } else {
                            $_SESSION['telat_waktu'] = "$detik_telat seconds late!";
                        }
                        $status = 'Late';
                    } else {
                        $_SESSION['telat'] = false;
                        $status = 'On-time';
                    }
                    $update_query = "UPDATE time SET status = '$status' WHERE user_id = $user_id AND tanggal = '$tanggal'";
                    mysqli_query($koneksi, $update_query);
                }
            } else {
                $batas_waktu = strtotime('08:00:00');
                $waktu_login = strtotime($time_login);
        
                if ($waktu_login > $batas_waktu) {
                    $_SESSION['telat'] = true;
                    $selisih     = $waktu_login - $batas_waktu;
                    $menit_telat = floor($selisih / 60);
                    $jam_telat   = floor($menit_telat / 60);
                    $sisa_menit_telat = $menit_telat % 60;
                    $detik_telat = $selisih % 60;
        
                    if ($jam_telat > 0) {
                        $_SESSION['telat_waktu'] = "$jam_telat hours $sisa_menit_telat minutes $detik_telat seconds late!";
                    } elseif ($sisa_menit_telat > 0) {
                        $_SESSION['telat_waktu'] = "$sisa_menit_telat minutes $detik_telat seconds late!";
                    } else {
                        $_SESSION['telat_waktu'] = "$detik_telat seconds late!";
                    }
                    $status = 'Late';
                } else {
                    $_SESSION['telat'] = false;
                    $status = 'On-time';
                }
                $insert_query = "INSERT INTO time (tanggal, user_id, time_login, geotagging, status) VALUES ('$tanggal', $user_id, '$time_login', '', '$status')";
                mysqli_query($koneksi, $insert_query);
            }
        }
        
        header("Location: dashboard.php");
        exit;
        
    } else {
        header("Location: Halaman_login.php");
        exit;
    }
?>