<?php
require_once 'includes/function.php';

// Get all users for display
$users = getAllUsers();
$userCount = count($users);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>User Management System</h1>
        
        <!-- Add User Form -->
        <div class="form-container">
            <h2 class="form-title">Add New User</h2>
            
            <!-- Alert Container -->
            <div id="alertContainer"></div>
            
            <form id="userForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="text" id="phone" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label for="address">Address *</label>
                        <textarea id="address" name="address" required></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <button type="submit" id="submitBtn" class="btn">Add User</button>
                </div>
            </form>
        </div>
        
        <!-- Users Table -->
        <div class="table-container">
            <h2 class="table-title">
                Users List 
                <span style="font-size: 0.8em; color: #888;">
                    (Total: <span id="userCount"><?php echo $userCount; ?></span>)
                </span>
            </h2>
            
            <?php if (empty($users)): ?>
                <div id="noUsersMessage" style="text-align: center; padding: 40px; color: #888;">
                    <p>No users found. Add the first user above!</p>
                </div>
            <?php else: ?>
                <div id="noUsersMessage" style="display: none; text-align: center; padding: 40px; color: #888;">
                    <p>No users found. Add the first user above!</p>
                </div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                            <td><?php echo htmlspecialchars($user['address']); ?></td>
                            <td>
                                <?php 
                                $date = new DateTime($user['created_at']);
                                echo $date->format('M j, Y g:i A');
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>