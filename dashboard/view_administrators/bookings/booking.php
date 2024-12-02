<?php
  include '../../conn.php';

  // Mulai session untuk notifikasi
  session_start();

  // Inisialisasi variabel notifikasi
  $error = $_SESSION['error'] ?? '';
  $success = $_SESSION['success'] ?? '';

  // Hapus notifikasi setelah ditampilkan
  unset($_SESSION['error'], $_SESSION['success']);

  
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_add'])) {
      try {
        // Validasi input
        $id_customer = htmlspecialchars($_POST['id_customer']);
        $check_in = htmlspecialchars($_POST['check_in']);
        $check_out = htmlspecialchars($_POST['check_out']);
        $room = htmlspecialchars($_POST['room']);
        $quantity = htmlspecialchars($_POST['quantity']);

        // Validasi input sebelum proses
        if (empty($id_customer) || empty($check_in) || empty($check_out) || empty($room) || empty($quantity)) {
          throw new Exception("All fields are required!");
        }

        // Tentukan harga berdasarkan tipe kamar
        switch ($room) {
          case "executive":
            $total_price = 2000;
            break;
          case "luxury":
            $total_price = 55000;
            break;
          case "presidential":
            $total_price = 150000;
            break;
          default:
            throw new Exception("Invalid room type!");
        }

        // Cek ketersediaan kamar sebelum booking
        $stmt_check = $pdo->prepare("
          SELECT COUNT(*) as booked_rooms 
          FROM booking 
          WHERE room = :room 
          AND (
            (check_in <= :check_in AND check_out >= :check_in) OR 
            (check_in <= :check_out AND check_out >= :check_out) OR
            (check_in >= :check_in AND check_out <= :check_out)
          )
        ");
        $stmt_check->bindParam(':room', $room);
        $stmt_check->bindParam(':check_in', $check_in);
        $stmt_check->bindParam(':check_out', $check_out);
        $stmt_check->execute();
        $result = $stmt_check->fetch(PDO::FETCH_ASSOC);

        // Batas maksimum kamar
        $max_rooms = [
          'executive' => 65,
          'luxury' => 25,
          'presidential' => 10
        ];

        // Cek apakah masih ada kamar tersedia
        if ($result['booked_rooms'] + $quantity > $max_rooms[$room]) {
          throw new Exception("Not enough rooms available for the selected type!");
        }

        // Booking hanya satu kali
        $stmt = $pdo->prepare("INSERT INTO booking(id_customer, check_in, check_out, room, total_price) VALUES (:id_customer, :check_in, :check_out, :room, :total_price)");
        $stmt->bindParam(':id_customer', $id_customer);
        $stmt->bindParam(':check_in', $check_in);
        $stmt->bindParam(':check_out', $check_out);
        $stmt->bindParam(':room', $room);
        $total_booking_price = $total_price * $quantity;
        $stmt->bindParam(':total_price', $total_booking_price);

        // Jalankan kode
        $stmt->execute();

           // Pesan sukses
           $_SESSION['success'] = "New data added successfully!";
           //agar submit tidak diulangi ketika web di refresh
           header("Location: " . $_SERVER['PHP_SELF']);
           exit(); 
         }catch (PDOException $e) {
           $_SESSION['error'] = "Error adding data: " . $e->getMessage();
         }
       }
    // Menghapus data berdasarkan ID
    if (isset($_POST['submit_delete'])) {
      $id = htmlspecialchars($_POST['id']);

      try {
          // Prepare statement untuk menghapus data
          $stmt = $pdo->prepare("DELETE FROM booking WHERE id = :id");
          $stmt->bindParam(':id', $id, PDO::PARAM_INT);

          // Eksekusi query
          $stmt->execute();

             // Pesan sukses
             $_SESSION['success'] = "data deleted successfully!";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error deleting data: " . $e->getMessage();
            }

      // Redirect untuk mencegah form resubmission
      header("Location: " . $_SERVER['PHP_SELF']);
      exit();
    }
  }
  ?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moonlit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="booking.css"> 
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orelega+One&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Luxurious+Roman&display=swap" rel="stylesheet">

</head>
<body>
     <!-- Navbar -->
   <nav class="navbar d-flex justify-content-between">
    <button id="menu-toggle" class="menu-toggle">
      <i class="fas fa-bars"></i> 
    </button>
    <div class="logout-container">
    <a href="../../view_customers/login.php" class="logout">Logout</a>
    </div>
  </nav>

  <!-- Overlay and Sidebar -->
  <div class="overlay"></div>
  <div class="sidebar">
    <div class="close-icon">
      <i class="fas fa-times"></i>
    </div>
    <div class="admin-section  d-flex align-items-center mb-4">
      <i class="fas fa-user-circle fa-3x text-white me-3"></i>
      <h3 class="text-white mb-0 nav-links">Administrator</h3>
    </div>
    <ul class="nav-links">
      <li><a href="../dashboard.php">Dashboard</a></li>
      <li><a href="../rooms/room.php">Room Management</a></li>
      <li><a href="../customers/customer.php">Customer Management</a></li>
      <li><a href="booking.php">Booking Management</a></li>
      <li><a href="../payments/payment.php">Payment Management</a></li>
      <li><a href="../additionals/additionalservices.php">Additional Services Management</a></li>
      <li><a href="../staffs/staff.php">Staff Management</a></li>
      <li><a href="../salarys/staffsalary.php">Staff Salary Management</a></li>
      <li><a href="../managers/manager.php">Manager Management</a></li>
    </ul>
  </div>

  <div class="container">
    <h1 class="mb-4">Booking Management</h1>

 <!-- Notification Messages -->
<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <?php if (!empty($_SESSION['isDelete']) && $_SESSION['isDelete']): ?>
        <!-- Notifikasi delete berhasil (warna merah) -->
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php else: ?>
        <!-- Notifikasi umum (warna hijau) -->
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

    
    <div class="row">
      <!-- Tabel -->
      <div class="col-md-12">
        <div class="table-responsive"> <!-- Make the table scrollable on smaller screens -->
          <table class="table table-bordered">
            <thead class="table-primary">
              <tr>
                <th>ID Booking</th>
                <th>ID Customer</th>
                <th>Check-In</th>
                <th>Check-out</th>
                <th>Rooms</th>
                <th>Total Price</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php 
                $data = [];
                try {
                    $stmt = $pdo->query("SELECT * FROM booking ORDER BY id");
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $message = "Error fetching data: " . $e->getMessage();
                }
              ?>
              <?php if(!empty($data)) : ?>
                <?php foreach($data as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['id']); ?></td>
                            <td><?php echo htmlspecialchars($item['id_customer']); ?></td>
                            <td><?php echo htmlspecialchars($item['check_in']); ?></td>
                            <td><?php echo htmlspecialchars($item['check_out']); ?></td>
                            <td><?php echo htmlspecialchars($item['room']); ?></td>
                            <td><?php echo htmlspecialchars($item['total_price']); ?></td>
                            <td>
                              <form method="POST" onsubmit="return confirm('Are you sure you want to delete this service?');" style="display:inline;">
                                <input type="hidden" name="submit_delete" value="1">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($item['id']); ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                              </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                      <td colspan="7" class="text-center">No data available</td>
                  </tr>
                <?php endif; ?>
            </tbody>
          </table>
        </div>

      <!-- Form Add New Booking -->
      <div id="serviceFormContainer">
        <form action="" method="POST">
        <input type="hidden" name="submit_add" value="1">
        <div class="border border-black p-3" id="serviceForm">
          <header class="mb-4 text-start fw-bold fs-5 pt-3" style="color: #2c5099;">Add New Booking</header>
          <!-- ID Customer -->
          <div class="d-flex align-items-center mb-3">
            <label for="idcustomer" class="section-title me-2 flex-shrink-0" style="min-width: 130px;">ID Customer</label>
            <input name="id_customer" type="text" id="idcustomer" class="form-control flex-grow-1" value="">
          </div>
          <!-- Check-In -->
          <div class="d-flex align-items-center mb-3">
            <label for="checkIncheck_out" class="section-title me-2 flex-shrink-0" style="min-width: 130px;">Check-In</label>
            <input name="check_in" type="datetime-local" id="check_out" class="form-control flex-grow-1">
          </div>
          <!-- Check-Out -->
          <div class="d-flex align-items-center mb-3">
            <label for="checkOutcheck_out" class="section-title me-2 flex-shrink-0" style="min-width: 130px;">Check-Out</label>
            <input name="check_out" type="datetime-local" id="check_out" class="form-control flex-grow-1">
          </div>

          <!-- Rooms -->
          <div class="d-flex flex-column mb-3" id="roomsContainer">
            <label class="section-title mb-2" style="font-size: 18px;">Rooms</label>

            <!-- Template untuk memilih tipe kamar dan jumlah kamar -->
            <div class="d-flex align-items-center mb-2 room-entry">
              <!-- Pemilihan Tipe Kamar -->
              <div class="d-flex flex-column flex-grow-1 me-2">
                <label class="section-title" for="roomType">Room Type</label>
                <select class="form-select" id="roomType" name="room">
                  <option value="" disabled selected>Select Room Type</option>
                  <option value="executive">Executive Suite</option>
                  <option value="luxury">Luxury Suite</option>
                  <option value="presidential">Presidential Suite</option>
                </select>
              </div>

              <!-- Input Jumlah Kamar -->
              <div class="d-flex flex-column flex-grow-1">
                <label class="section-title" for="roomQuantity">Quantity</label>
                <input name="quantity" type="number" id="roomQuantity" class="form-control" name="roomQuantity" placeholder="Enter Quantity" min="1">
              </div>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="text-end">
            <button type="submit" class="btn btn-primary rounded-3 fw-bold" id="addingService">Save</button>
          </div>
        </div>
        </form>
      </div>
    </div>
  </div>



    <script src="../../sidebar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
  </body>
</html>
