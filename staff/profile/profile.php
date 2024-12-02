<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../../login/login.php");
    exit();
}

include '../../config/db.php';

// Fetch user details based on the session user ID
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Information</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../components/css/profile.css">
    <link rel="icon" href="../../components/image/book.png" type="image/x-icon">
</head>
<body>
    <!-- Navbar -->
    <?php include '../layout/navbar.php'; ?>

    <!-- Main Content -->
    <div class="content-wrapper">
        <div class="container">
            <div class="centered-heading">
                <h2>Profile Information</h2>
            </div>

            <!-- Profile Info Section (Read-Only) -->
            <div class="profile-info">
                <form>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="first_name" class="form-label">First Name:</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" disabled>
                        </div>

                        <div class="col-md-4">
                            <label for="middle_name" class="form-label">Middle Name:</label>
                            <input type="text" class="form-control" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($user['middle_name']); ?>" disabled>
                        </div>

                        <div class="col-md-4">
                            <label for="last_name" class="form-label">Last Name:</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" disabled>
                        </div>

                        <div class="col-md-4">
                            <label for="suffix" class="form-label">Suffix:</label>
                            <input type="text" class="form-control" id="suffix" name="suffix" value="<?php echo htmlspecialchars($user['suffix']); ?>" disabled>
                        </div>

                        <div class="col-md-4">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        </div>

                        <div class="col-md-4">
                            <label for="user_type" class="form-label">User Type:</label>
                            <input type="text" class="form-control" id="user_type" name="user_type" value="<?php echo htmlspecialchars($user['user_type']); ?>" disabled>
                        </div>

                        <div class="col-md-4">
                            <label for="borrow_limit" class="form-label">Borrow Limit:</label>
                            <input type="text" class="form-control" id="borrow_limit" name="borrow_limit" value="<?php echo htmlspecialchars($user['borrow_limit']); ?>" disabled>
                        </div>

                        <div class="col-md-4">
                            <label for="date_of_birth" class="form-label">Date of Birth:</label>
                            <input type="text" class="form-control" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($user['date_of_birth']); ?>" disabled>
                        </div>

                        <div class="col-md-4">
                            <label for="street" class="form-label">Street:</label>
                            <input type="text" class="form-control" id="street" name="street" value="<?php echo htmlspecialchars($user['street']); ?>" disabled>
                        </div>

                        <div class="col-md-4">
                            <label for="purok" class="form-label">Purok:</label>
                            <input type="text" class="form-control" id="purok" name="purok" value="<?php echo htmlspecialchars($user['purok']); ?>" disabled>
                        </div>

                        <div class="col-md-4">
                            <label for="barangay" class="form-label">Barangay:</label>
                            <input type="text" class="form-control" id="barangay" name="barangay" value="<?php echo htmlspecialchars($user['barangay']); ?>" disabled>
                        </div>

                        <div class="col-md-4">
                            <label for="city" class="form-label">City:</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($user['city']); ?>" disabled>
                        </div>

                        <div class="col-md-4">
                            <label for="phone_number" class="form-label">Phone Number:</label>
                            <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" disabled>
                        </div>

                        <div class="col-md-4">
                            <label for="status" class="form-label">Status:</label>
                            <input type="text" class="form-control" id="status" name="status" value="<?php echo htmlspecialchars($user['status']); ?>" disabled>
                        </div>

                        <div class="col-md-4">
                            <label for="membership_id" class="form-label">Membership ID:</label>
                            <input type="text" class="form-control" id="membership_id" name="membership_id" value="<?php echo htmlspecialchars($user['membership_id']); ?>" disabled>
                        </div>
                    </div>
                </form>

                <!-- Button to Open Modal -->
                <button type="button" class="btn btn-primary mt-3 w-100" data-bs-toggle="modal" data-bs-target="#passwordUpdateModal">
                    Update Password
                </button>
            </div>

            <!-- Password Update Modal -->
            <div class="modal fade" id="passwordUpdateModal" tabindex="-1" aria-labelledby="passwordUpdateModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="passwordUpdateModalLabel">Update Password</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Displaying error or success messages inside the modal -->
                            <?php if (isset($_SESSION['error_message'])): ?>
                                <div class="alert alert-danger">
                                    <?php echo $_SESSION['error_message']; ?>
                                </div>
                                <?php unset($_SESSION['error_message']); ?>
                            <?php elseif (isset($_SESSION['success_message'])): ?>
                                <div class="alert alert-success">
                                    <?php echo $_SESSION['success_message']; ?>
                                </div>
                                <?php unset($_SESSION['success_message']); ?>
                            <?php endif; ?>

                            <!-- Form remains intact after submission -->
                            <form id="passwordUpdateForm">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password:</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password:</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>

                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password:</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>

                                <button type="submit" class="btn btn-success mt-3 w-100">Update Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Bootstrap JS and JQuery -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $("#passwordUpdateForm").on("submit", function(event) {
            event.preventDefault();

            // Collect form data
            var currentPassword = $("#current_password").val();
            var newPassword = $("#new_password").val();
            var confirmPassword = $("#confirm_password").val();

            // Validate if new password and confirm password match
            if (newPassword !== confirmPassword) {
                $('#passwordUpdateModal .modal-body').html(`
                    <div class="alert alert-danger">New password and confirm password do not match.</div>
                    <button class="btn btn-primary mt-3 w-100" id="reloadPage">Okay</button>
                `);
                $('#reloadPage').on("click", function() {
                    location.reload();
                });
                return; // Stop execution here
            }

            // Send the form data via AJAX to the backend
            $.ajax({
                url: 'function.php',  // Endpoint where form data will be processed
                method: 'POST',
                data: {
                    current_password: currentPassword,
                    new_password: newPassword,
                    confirm_password: confirmPassword
                },
                success: function(response) {
                    // Parse JSON response
                    var jsonResponse = JSON.parse(response);

                    // Update the modal content based on the response status
                    if (jsonResponse.status === 'success') {
                        $('#passwordUpdateModal .modal-body').html(`
                            <div class="alert alert-success">${jsonResponse.message}</div>
                            <button class="btn btn-primary mt-3 w-100" id="reloadPage">Okay</button>
                        `);
                    } else {
                        $('#passwordUpdateModal .modal-body').html(`
                            <div class="alert alert-danger">${jsonResponse.message}</div>
                            <button class="btn btn-primary mt-3 w-100" id="reloadPage">Okay</button>
                        `);
                    }

                    // Attach the reload function to the button
                    $('#reloadPage').on("click", function() {
                        location.reload();
                    });
                },
                error: function() {
                    // Handle AJAX errors
                    $('#passwordUpdateModal .modal-body').html(`
                        <div class="alert alert-danger">An error occurred. Please try again.</div>
                        <button class="btn btn-primary mt-3 w-100" id="reloadPage">Okay</button>
                    `);
                    
                    // Attach the reload function to the button
                    $('#reloadPage').on("click", function() {
                        location.reload();
                    });
                }
            });
        });
    </script>
</body>
</html>