<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "userdb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Insert a new user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'insert') {
    $username = $_POST['name'];
    $email = $_POST['email'];
    $time = $_POST['created_at'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (`name`, `email`, `created_at`) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $time);
    $stmt->execute();
    $stmt->close();
}

// Update user information
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = $_POST['id'];
    $username = $_POST['name'];
    $email = $_POST['email'];
    
    $stmt = $conn->prepare("UPDATE users SET `name` = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $username, $email, $id);
    $stmt->execute();
    $stmt->close();
    header("Refresh:0; url=index.php");
}

// Delete a user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = $_POST['id'];
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Retrieve and display users
$result = $conn->query("SELECT * FROM users");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TASK 1</title>
    <link rel="stylesheet" href="includes/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1 style="text-align: center;">User Management</h1>
    
    <div class="section add-section">
        <h2>Add  User</h2>
        <form id="insertForm" method="post">
            <input type="hidden" name="action" value="insert">
            <input type="text" name="name" placeholder="name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="datetime-local" name="created_at" placeholder="Created At" required>
            <button type="submit">Add User</button>
        </form>
    </div>

<div class="section upd-section">
    <h2>Update User</h2>
    <form id="updateForm" method="post">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="updateId">
        <input type="text" name="name" id="updatename" placeholder="name" required>
        <input type="email" name="email" id="updateEmail" placeholder="Email" required>
        <button type="submit">Update User</button>
    </form>
</div>

    <!-- <h2>Delete User</h2>
    <form id="deleteForm" method="post">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
        <button type="submit">Delete User</button>
    </form> -->
    <h2 class="addPart">Add New User</h2>
        <button class="addUser addPart">Add User</button>

    <h2>User List</h2>
    <table id="userTable" border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr data-id="<?= $row['id'] ?>">
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['name'] ?></td>
                    <td><?= $row['email'] ?></td>
                    <td><?= $row['created_at'] ?></td>
                    <td>
                        <button class="editButton">Edit</button>
                        <button class="deleteButton">Delete</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <script>
        $(document).ready(function() {
            // Handle edit button click
            $('.editButton').click(function() {
                $('.upd-section').show();
                var row = $(this).closest('tr');
                var id = row.data('id');
                var name = row.find('td:eq(1)').text();
                var email = row.find('td:eq(2)').text();

                $('#updateId').val(id);
                $('#updatename').val(name);
                $('#updateEmail').val(email);
            });

            $('.add-section').hide();
            $('.upd-section').hide();
            // Handle delete button click
            $('.addUser').click(function() {                
                $('.add-section').show();
                $('.addPart').hide();
            });
            $('.deleteButton').click(function() {
                var row = $(this).closest('tr');
                var id = row.data('id');

                $('#deleteId').val(id);
                $('#deleteForm').submit();
            });
        });
    </script>
</body>
</html>

<?php
// Close the connection
$conn->close();
?>
