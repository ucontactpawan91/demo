<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Users</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">View users</h1>
        <table class="table table-bordered mt-4">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User Name</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Contact</th>
                    <th>Gender</th>
                    <th>State</th>
                    <th>City</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                include 'db.php';

                $sql = "SELECT * FROM users";
                $result = $conn->query($sql);

                if($result->num_rows > 0){
                    while($row = $result->fetch_assoc()){
                        echo "<tr>";
                        echo "<td>" . $row["id"]."</td>";
                        echo "<td>" . $row["username"]."</td>";
                        echo "<td>" . $row["email"]."</td>";
                        echo "<td>" . $row["address"]."</td>";
                        echo "<td>" . $row["contact"]."</td>";
                        echo "<td>" . $row["gender"]."</td>";
                        echo "<td>" . $row["state"]."</td>";
                        echo "<td>" . $row["city"]."</td>";
                        echo "<td><a href='edit.php?id=" .$row['id']. " ' class='btn btn-warning btn-sm'>Edit</a>";
                        echo "<a href='delete.php?id=" .$row['id']. " ' class='btn btn-danger btn-sm'>Delete</a> </td>";
                    }
                } else{
                    echo "<tr><td colspan='9' class='text-center'>No users found</td></tr>";

                }

               $conn->close();
               ?>  
            </tbody>
        </table>
         <div class="mt-4">
          <a href="index.php" class="btn btn-secondary">Back to dashboard</a>
       </div>
     </div>
    
 </body>
 </html> 