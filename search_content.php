<?php
    session_start();
    require 'db_connect.php';

    $result = NULL;

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST["text"])) {
        $search = htmlspecialchars(trim($_POST['text']));
        $user_id = $_SESSION['user_id']; 

        if (!empty($search)) {
            
            $title = "%".$search."%";  // Add wildcards to the title search term
            $sql = "SELECT t.*, l.name as list_name FROM tasks as t JOIN list as l ON (l.id = t.list_id) WHERE t.user_id = ? AND t.title LIKE ? ORDER BY t.due_date";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $user_id, $title);  // Bind the title parameter with wildcards
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            
        } else {
            echo "Judul tugas tidak boleh kosong.";
        }
    }
    else{
        header("location: /project_UTS_lab/index.php");
    }
    $conn->close(); 
    
?>

<html>



    <ul class="list-group" id="search-list" style="">
        <?php if($result){while($row = $result -> fetch_assoc()){ ?>
            <li class="list-group-item" style="margin: 0 1vw 2vh 1vw">
            <a href="/project_UTS_lab/index.php/<?php echo $row['list_id'] ?>" class="list-group-item list-group-item-action active" aria-current="true">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1"><?php echo $row['title']; ?></h5>
                    <small><?php echo $row['status']?></small>
                </div>
                    <p class="mb-1"><?php echo $row['description']?></p>
                <small><?php echo $row['list_name']?></small>
            </a>
              
            </li>
        <?php 
        }}
        ?>
    </ul>

</html>