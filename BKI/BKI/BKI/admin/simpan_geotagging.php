<?php
    session_start();
    include("koneksi.php");

    if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'User') {
        $user_id = $_SESSION['user_id'];
        $data = json_decode(file_get_contents("php://input"), true);

        if (isset($data['latitude']) && isset($data['longitude']) && isset($data['type'])) {
            $latitude  = $data['latitude'];
            $longitude = $data['longitude'];
            $type = $data['type'];

            if (is_numeric($latitude) && is_numeric($longitude) && $latitude >= -90 && $latitude <= 90 && $longitude >= -180 && $longitude <= 180) {
                $geotagging = $latitude . ',' . $longitude;

                $column = '';
                switch ($type) {
                    case 'login':
                        $column = 'geotagging';
                        break;
                    case 'before_break':
                        $column = 'geotagging_before_break';
                        break;
                    case 'after_break':
                        $column = 'geotagging_after_break';
                        break;
                    case 'logout':
                        $column = 'geotagging_logout';
                        break;
                    default:
                        echo json_encode(['status' => 'error', 'message' => 'Invalid type']);
                        exit;
                }

                $check_query  = "SELECT $column FROM time WHERE user_id = $user_id AND DATE(tanggal) = CURDATE()";
                $check_result = mysqli_query($koneksi, $check_query);
                $row = mysqli_fetch_assoc($check_result);

                if (empty($row[$column])) {
                    $update_query = "UPDATE time SET $column = '$geotagging' WHERE user_id = $user_id AND DATE(tanggal) = CURDATE()";
                    $result = mysqli_query($koneksi, $update_query);

                    if ($result) {
                        echo json_encode(['status' => 'success']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan geotagging', 'query' => $update_query]);
                    }
                } else {
                    echo json_encode(['status' => 'success', 'message' => 'Geotagging sudah ada']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Data lokasi tidak valid']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data lokasi tidak lengkap']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User tidak terautentikasi atau role tidak sesuai']);
    }
?>