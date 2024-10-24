<?php
    session_start();

    if(!$_SESSION['user_id']){
        header("location: /project_UTS_lab/login.php");
        exit();
    };
 
    require 'db_connect.php';
    
    $error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
    unset($_SESSION['error']);

    // Get the request URI
    $requestUri = $_SERVER['REQUEST_URI'];

    // Remove query strings
    $requestUri = strtok($requestUri, '?');

    // Trim the leading and trailing slashes
    $requestUri = trim($requestUri, '/');

    // Split the URI into segments
    $segments = explode('/', $requestUri);


    if($segments == "add_task.php"){
        header("Location: add_task.php");;
    }

    date_default_timezone_set("Asia/Jakarta");
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM list WHERE user_id = ? order by id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $list_result = $stmt->get_result();
    $list_data = [];
    while ($row = $list_result->fetch_assoc()) {
        $list_data[] = $row;
    }

    $user_id = $_SESSION['user_id'];
    $sql = "SELECT email, username FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user_result_list = $user_result -> fetch_assoc();


    

    function get_lengthofgroup($stmt){
        $_SESSION['complete_length'] = 0;
        $_SESSION['overdue_length'] = 0;
        $_SESSION['pendding_length'] = 0;
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if($row['status'] == 'completed'){
                $_SESSION['complete_length'] = $row['lengths'];
            }
            else if($row['status'] == 'overdue'){
                $_SESSION['overdue_length'] = $row['lengths'];
               

            }
            else if($row['status'] == 'pending'){
                $_SESSION['pendding_length'] = $row['lengths'];
            };

        };
    }
  

    // var_dump($segments);
    if(count($segments) > 2){
        if($segments[2] === "today"){
            $sql = "SELECT * FROM tasks WHERE user_id = ? AND DATE(due_date) = ?  ORDER BY status, due_date";
            $stmt = $conn->prepare($sql);
            $todaydate = date('Y-m-d');
            $stmt->bind_param("ss", $user_id, $todaydate);
            $stmt->execute();
            $result = $stmt->get_result();
            $sql = "SELECT status, COUNT(status) as lengths FROM tasks WHERE user_id = ? AND DATE(due_date) = ? GROUP BY status";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $user_id, $todaydate);
            get_lengthofgroup($stmt);
            $stmt->close();
            $conn->close();
            $nama_list = "Today";
        }
        
        else{
            $sql = "SELECT * FROM tasks WHERE user_id = ? AND list_id = ? ORDER BY status, due_date";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $user_id, $segments[2]);
            $stmt->execute();
            $result = $stmt->get_result();
            $_SESSION['list_id'] = $segments[2];

            $sql = "SELECT * FROM list WHERE id = ? and user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $segments[2], $user_id);
            $stmt->execute();
            $temp = $stmt -> get_result();
            $temp_nama_list = $temp->fetch_assoc();

            if($temp_nama_list){
                $nama_list = $temp_nama_list['name'];
            }
            else{
                header("location: /project_UTS_lab/");
            }
            $sql = "SELECT status, COUNT(status) as lengths FROM tasks WHERE user_id = ? AND list_id = ? GROUP BY status";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $user_id, $segments[2]);
            get_lengthofgroup($stmt);
            $stmt->close();
            $conn->close();
        }

        
    }

    else{
        $user_id = $_SESSION['user_id']; 
        $sql = "SELECT * FROM tasks WHERE user_id = ?  AND DATE(due_date) >= ? ORDER BY status, due_date";
        $stmt = $conn->prepare($sql);
        $todaydate = date('Y-m-d');
        $stmt->bind_param("ss", $user_id, $todaydate);
        $stmt->execute();
        $result = $stmt->get_result();
        $nama_list = "Dashboard";
        $sql = "SELECT status, COUNT(status) as lengths FROM tasks WHERE user_id = ?  AND DATE(due_date) >= ? GROUP BY status";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $user_id, $todaydate);
        get_lengthofgroup($stmt);
        $stmt->close();
        $conn->close();
    
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TodoUMN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        
        .sidebar {
            height: 100vh;
        }


    </style>


    
</head>
<body>

    <!-- Sidebar Container -->
    <div class="container-fluid">
        <div class="row">

        <div class="modal fade" id="exampleModal" tabindex="-1"  data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Confirm Delete</h1>
                        <button type="button" class="btn-close" id="cancelDeleteBtn" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete this item?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="cancelDeleteBtn" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="exampleModals" tabindex="-1" aria-labelledby="ModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content" style="min-height: 60vh;">
                    <div class="modal-header">
                        
                        <div class="flex-grow-1 text-center">
                            <span class="fw-bold">Search Content</span>
                         </div>
                        
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input class="form-control" id = "search_input" oninput="refreshSearchSection(this.value)" type="text" placeholder="Search Content" aria-label="default input example">
                        <hr>
                        <div id="search-result-content">
                            

                        </div>
                    </div>
                    <div class="modal-footer">

                    </div>
                </div>
            </div>
        </div>


        <div class="modal fade" id="ProfileModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false"  aria-labelledby="ModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content" style="min-height: 60vh;">
                    <div class="modal-header">
                        
                        <div class="flex-grow-1 text-center">
                            <span class="fw-bold">Profile</span>
                         </div>
                        
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                    <form class="form-floating">
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="floatingInputValue" placeholder="name@example.com" value="<?php echo $user_result_list['email'] ; ?>" disabled readonly>
                            <label for="floatingInputValue">Email</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="floatingInputValues" placeholder="name@example.com" value="<?php echo $user_result_list['username']; ?>" disabled readonly>
                            <label for="floatingInputValues">Username</label>
                        </div>
                    </form>

                    

                    </div>
                    <div class="modal-footer">

                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="SettingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false"  aria-labelledby="ModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content" style="min-height: 60vh;">
                    <div class="modal-header">
                        
                        <div class="flex-grow-1 text-center">
                            <span class="fw-bold">Change Data</span>
                         </div>
                        
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                    <form action="/project_UTS_lab/update_data.php" method="POST" class="form-floating">
                        <div id="emailDiv" class="form-floating mb-3">
                            <input type="email" class="form-control" id="floatingInputValue" placeholder="name@example.com" name="email" value="<?php echo $user_result_list['email']; ?>">
                            <label for="floatingInputValue">Email</label>
                        </div>
                        <div id="usernameDiv" class="form-floating mb-3">
                            <input type="text" class="form-control" id="floatingInputValues" placeholder="name@example.com" name="username" value="<?php echo $user_result_list['username']; ?>">
                            <label for="floatingInputValues">Username</label>
                        </div>
                        <a href="#" id = "passwordHref" onclick="showDiv(0)">Change Password</a>
                    
                        <div id="OldpasswordDiv" class="form-floating mb-3 hidden">
                            <input type="password" class="form-control" id="floatingOldPassword" placeholder="Old Password" name="old_password">
                            <label for="floatingOldPassword">Old Password</label>
                        </div>
                        <div id="newPasswordDiv" class="form-floating mb-3 hidden">
                            <input type="password" class="form-control" id="floatingNewPassword" placeholder="New Password" name="new_password">
                            <label for="floatingNewPassword">New Password</label>
                        </div>
               
                        
                        <a href="#" id="UserHref" onclick="showDiv(showDiv(1))" class="hidden">Change Data</a>
                        <div id="dataDiv" class="d-grid gap-2 col-6 mx-auto">
                            <button class="btn btn-primary" type="submit">Submit</button>
                        </div>
                    </form>
                    </div>
                    <div class="modal-footer">

                    </div>
                </div>
            </div>
        </div>


        <nav class="navbar navbar-light bg-secondary d-md-none">
            <button class="navbar-toggler text-white" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
                <span class="navbar-toggler-icon"></span>
                Menu
            </button>
        </nav>

<!-- Sidebar -->
    <div class="offcanvas offcanvas-start d-md-none bg-secondary" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel" style="width: 60vw !important;">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title text-white" id="offcanvasSidebarLabel">To Do</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
    <div class="offcanvas-body">

        <nav class="flex-column">
                    <a href="#" onclick="retrieve_data()" class="nav-link text-white" id="search-expand" data-bs-toggle="modal" data-bs-target="#exampleModals">
                        Search
                    </a>
                    <a href="/project_UTS_lab/index.php/" class="nav-link active text-white">
                        Home
                    </a>
                    <a href="/project_UTS_lab/index.php/today" class="nav-link text-white">
                        Today
                    </a>
                  
                    <!-- My List Section -->
                    <hr>
                    <span class="fs-4 text-white">My List</span>
                    <div class="container todo-container">
                        <?php
                        $counter = 0;
                        foreach ($list_data as $row) {
                            if($counter <= 1) $counter++;
                        ?>
                            <div class="todo-list d-flex flex-row align-items-center mb-2">
                                <a href="<?php echo "/project_UTS_lab/index.php/" . $row['id']; ?>" class="nav-link active text-white flex-grow-1">
                                    <?php echo $row['name']; ?>
                                </a>
                                <?php if ($counter != 1) { ?>
                                    <button type="button" data-bs-toggle="modal" data-bs-target="#exampleModal" style="background: transparent; border: none;" onclick="setDeleteId('<?php echo $row['id']; ?>')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                            <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                                            <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                                        </svg>
                                    </button>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                </nav>
            </div>

                 
            <form onsubmit="addList('listInput1')"  class="row g-3" style="padding: 0 0.5vw !important;">                        
                <input type="text" class="form-control" name="list" id="listInput1" placeholder="Tambahkan List" required></input>
            </form>
        

            <div class="dropdown mt-auto ps-2" style="padding-bottom: 5vh !important; margin-top: 2vh !important">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
                    <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                    <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1"/>
                </svg>
                    <?php echo "<strong>" . $_SESSION['username'] . "</strong>"; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark shadow">
                    <li><a class="dropdown-item" href="#" id="search-expand" data-bs-toggle="modal" data-bs-target="#SettingModal">Settings</a></li>
                    <li><a class="dropdown-item" href="#" id="search-expand" data-bs-toggle="modal" data-bs-target="#ProfileModal">Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="/project_UTS_lab/logout.php">Sign out</a></li>
                </ul>
            </div>
            
        </div>


            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-none d-md-block bg-secondary sidebar position-sticky" style="padding: 0; height: 100vh;overflow-x:hidden;">
            <div class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none p-3">
                <span class="fs-4">To Do</span>
            </div>
            <div class="d-flex flex-column" style="height: 73vh!important; overflow:hidden!important;">
                <div class="sidebar-option sidebar" style="max-height: 73vh!important; overflow-x:hidden">
                    <ul class="nav flex-column flex-grow-1" style="margin-top: 5vh;">
                        <li class="nav-item">
                        <a href="#" onclick = "retrieve_data()" class="nav-link text-white" id = "search-expand" data-bs-toggle="modal" data-bs-target="#exampleModals">
                            Search
                        </a>

                        </li>
                        <li class="nav-item">
                            <a href="/project_UTS_lab/index.php/" class="nav-link active text-white">
                                Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/project_UTS_lab/index.php/today" class="nav-link text-white">
                                Today
                            </a>
                        </li>
                       
                    </ul>
                    <hr>
                    <div class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none p-3">
                        <span class="fs-4">My List</span>
                    </div>
                    <div class="container todo-container">
                    <?php
                        $counter = 0;
                        foreach ($list_data as $row) {
                            if($counter <= 1) $counter++;
                        ?>
                            <div class="todo-list d-flex flex-row align-items-center mb-2">
                                <a href="<?php echo "/project_UTS_lab/index.php/" . $row['id']; ?>" class="nav-link active text-white flex-grow-1">
                                    <?php echo $row['name']; ?>
                                </a>
                                <?php if ($counter != 1) { ?>
                                    <button type="button" data-bs-toggle="modal" data-bs-target="#exampleModal" style="background: transparent; border: none;" onclick="setDeleteId('<?php echo $row['id']; ?>')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                            <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                                            <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                                        </svg>
                                    </button>
                                <?php } ?>
                            </div>
                        <?php } ?>


                    </div>
                </div>
            </div>

                
            <form onsubmit="addList('listInput2')"  class="row g-3" style="padding: 0 0.5vw !important;">                        
                <input type="text" class="form-control" name="list" id="listInput2" placeholder="Tambahkan List" required></input>
            </form>
        

            <div class="dropdown mt-auto ps-2" style="padding-bottom: 5vh !important; margin-top: 2vh !important">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
                    <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                    <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1"/>
                </svg>
                    <?php echo "<strong>" . $_SESSION['username'] . "</strong>"; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark shadow">
                    <li><a class="dropdown-item" href="#" id="search-expand" data-bs-toggle="modal" data-bs-target="#SettingModal">Settings</a></li>
                    <li><a class="dropdown-item" href="#" id="search-expand" data-bs-toggle="modal" data-bs-target="#ProfileModal">Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="/project_UTS_lab/logout.php">Sign out</a></li>
                </ul>
            </div>
        </nav>




   
        <main class="col-md-9 flex-grow-1 ms-sm-auto  px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $nama_list  ?></h1>
            </div>
            <br>
            <br>

            <select class="form-select" onChange="filterTasks(event)" aria-label="Default select" style="width: 10vw; margin-left: 1.2vw;">
                <option selected value="All">All</option>
                <option value="pendding">Pending</option>
                <option value="completed">Completed</option>
            </select>


            


            <div class="accordion accordion-flush" id="accordionFlushExample">
                <div class="container overflow-scroll"  id="main-section" style="height: 70vh;overflow-x:hidden!important;">
                    <div class="d-flex flex-column h-100">
                        <ul class="nav flex-column flex-grow-1" style="margin-top: 5vh;">
                            <?php 
                      
                                echo "<ul>";
                                echo '<div id= pendding>';
                                if ($_SESSION['pendding_length'] > 0) {
                                    echo '<div class="accordion" id="accordionExample">';
                                    echo '<div class="accordion-item">';
                                    echo '<h2 class="accordion-header" id="headingTwo">';
                                    echo '<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">';
                                    echo 'Pending';
                                    echo '</button>';
                                    echo '</h2>';
                                    echo '<div id="collapseTwo" class="accordion-collapse collapse show" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">';
                                    echo '<div class="accordion-body">';
                                
                                    for ($i = 1; $i <= $_SESSION['pendding_length']; $i++) {
                                        $row = $result->fetch_assoc();
                                        echo "<li class='d-flex justify-content-between align-items-center'>";
                                        $statusClass = ($row['status'] === 'completed') ? 'checked' : '';
                                        echo '<span class="custom-checkbox ' . $statusClass . '" onclick="toggleCheck(this, ' . $row['id'] . ')"></span>';
                                        echo "<span class='task-text'>" . htmlspecialchars($row['title']) . " - " . htmlspecialchars($row['description']) . " (due: " . htmlspecialchars($row['due_date']) . ")</span>";
                                        echo '<button type="button" data-bs-toggle="modal" data-bs-target="#exampleModal" style="background: transparent; border: none;" onclick="setDeleteTask(' . $row['id'] . ')">';
                                        echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">';
                                        echo '<path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>';
                                        echo '<path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>';
                                        echo '</svg>';
                                        echo '</button>';
                                        echo "</li>";
                                    }
                                
                                    echo '</div>';
                                    echo '</div>'; 
                                    echo '</div>'; 
                                    echo '</div>'; 
                                }
                                echo '</div>';
                       
                                echo '<div id=completed style="margin-top: 2vh"> ';
                                if ($_SESSION['complete_length'] > 0) {
                                    echo '<div class="accordion" id="accordionExample">';
                                    echo '<div class="accordion-item">';
                                    echo '<h2 class="accordion-header" id="headingOne">';
                                    echo '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">';
                                    echo 'Completed';
                                    echo '</button>';
                                    echo '</h2>';
                                    echo '<div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">';
                                    echo '<div class="accordion-body">';
                                
                                    for ($i = 1; $i <= $_SESSION['complete_length']; $i++) {
                                        $row = $result->fetch_assoc();
                                        echo "<li class='d-flex justify-content-between align-items-center'>";
                                        $statusClass = ($row['status'] === 'completed') ? 'checked' : '';
                                        echo '<span class="custom-checkbox ' . $statusClass . '" onclick="toggleCheck(this, ' . $row['id'] . ')"></span>';
                                        echo "<span class='task-text'>" . htmlspecialchars($row['title']) . " - " . htmlspecialchars($row['description']) . " (due: " . htmlspecialchars($row['due_date']) . ")</span>";
                                        echo '<button type="button" data-bs-toggle="modal" data-bs-target="#exampleModal" style="background: transparent; border: none;" onclick="setDeleteTask(' . $row['id'] . ')">';
                                        echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">';
                                        echo '<path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>';
                                        echo '<path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>';
                                        echo '</svg>';
                                        echo '</button>';
                                        echo "</li>";
                                    }
                                
                                    echo '</div>'; 
                                    echo '</div>'; 
                                    echo '</div>'; 
                                    echo '</div>'; 
                                }
                                echo '</div>';
                                ?>
                        </ul>
                        <div class="d-grid gap-2">
                            <a class="btn btn-outline-light mt-2" data-bs-toggle="collapse" href="#collapseExample" aria-expanded="false" aria-controls="collapseExample">Tambah Task</a>
                        </div>
                    <div class="collapse" id="collapseExample">
                        <div class="card card-body w-auto">
                        <form id="addTaskForm" onsubmit="addTask()" method="POST" class="row g-3">
                            <div class="mb-3">
                                <textarea class="form-control" name="title" id="titleInput" placeholder="Judul Tugas" required></textarea>
                            </div>
                            <div class="mb-3">
                                <textarea class="form-control" name="description" id="descriptionInput" placeholder="Deskripsi Tugas" required></textarea>
                            </div>
                            <div class="col-auto mb-3">
                                <input type="datetime-local" class="form-control" name="date" id="dateInput" placeholder="Pick a date" min="">
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary" type="submit">Tambah Tugas</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const offcanvasSidebar = document.getElementById("offcanvasSidebar");
        const offcanvas = new bootstrap.Offcanvas(offcanvasSidebar);

        function checkScreenSize() {
            if (window.innerWidth >= 768 && offcanvasSidebar.classList.contains("show")) {
                offcanvas.hide(); // Programmatically hide the offcanvas
            }
        }

        // Check screen size on window resize
        window.addEventListener("resize", checkScreenSize);

        // Optional: Also check once when the page is loaded to make sure it's not open initially on large screens
        checkScreenSize();
    });

    var today = new Date();
    var todayFormatted = today.toISOString().slice(0, 16);
    document.getElementById('dateInput').min = todayFormatted;

    let deleteId = null;
    let deleteTaskId = null;

    function setDeleteId(id) {
        deleteId = id;
    };

    function setDeleteTask(id) {
        deleteTaskId = id;
    };

    document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
        if (deleteId !== null) {
            deleteList(deleteId);
            deleteId = null;
        } else if (deleteTaskId !== null) {
            deleteTask(deleteTaskId);
            deleteTaskId = null;
        }
        window.location.reload();
    });

    document.getElementById('cancelDeleteBtn').addEventListener('click', function () {
        deleteId = null;
        deleteTaskId = null;
        window.location.reload();
    });


    
        function filterTasks(event) {
            const value = event.target.value;
            const pendingElement = document.getElementById("pendding");
            const completedElement = document.getElementById("completed");
            const pendingCollapse = document.getElementById("collapseTwo");
            const completedCollapse = document.getElementById("collapseOne");

            if (value === "All") {
                pendingElement.style.display = "block";
                completedElement.style.display = "block";
                pendingCollapse.classList.add('show');
                completedCollapse.classList.add('show');
            } else if (value === "pendding") {
                pendingElement.style.display = "block";
                completedElement.style.display = "none";
                pendingCollapse.classList.add('show');
            } else if (value === "completed") {
                pendingElement.style.display = "none";
                completedElement.style.display = "block";
                completedCollapse.classList.add('show');
            }
        }



    

    function refreshMainSection() {
        var xhr = new XMLHttpRequest();
        xhr.open("GET", window.location.pathname, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var responseDiv = document.createElement('div');
                responseDiv.innerHTML = xhr.responseText;
                var newContent = responseDiv.querySelector('#main-section');
                document.getElementById("main-section").innerHTML = newContent.innerHTML;

            }
        };
        xhr.send();
    }


    function refreshSearchSection(search) {
    var xhr = new XMLHttpRequest();
        xhr.open("POST", "/Project_UTS_lab/search_content.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var responseDiv = document.createElement('div');
                responseDiv.innerHTML = xhr.responseText;
                var newContent = responseDiv.querySelector('#search-list');
                document.getElementById("search-result-content").innerHTML = newContent.innerHTML;
            }
        };
     xhr.send("text=" + encodeURIComponent(search));
    }


    function retrieve_data(){
        var search = document.getElementById("search_input").value = ""; 
        refreshSearchSection(search);
    }





    function toggleCheck(element, taskID) {
        element.classList.toggle('checked');

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "/project_UTS_lab/change_status.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {

                } else {
                    alert("Error: " + xhr.statusText);
                }
            }
        };

        xhr.send("id=" + encodeURIComponent(taskID));
        refreshMainSection();
    }


    function addList(inputId) {
        var xhr = new XMLHttpRequest();
        var listInput = document.getElementById(inputId);
        var list = listInput.value;
        xhr.open("POST", "/project_UTS_lab/add_list.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    alert("List added successfully.");
                    listInput.value = '';
                } else {
                    alert("Error: " + xhr.statusText);
                }
            }
        };

        xhr.send("list=" + encodeURIComponent(list));
    }
    function addTask() {


        var xhr = new XMLHttpRequest();
        var title = document.getElementById('titleInput').value;
        var description = document.getElementById('descriptionInput').value;
        var date = document.getElementById('dateInput').value;

        xhr.open("POST", "/project_UTS_lab/add_task.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {


                } else {
                    alert("Error: " + xhr.statusText);
                }
            }
        };

        xhr.send("title=" + encodeURIComponent(title) + "&description=" + encodeURIComponent(description) + "&date=" + encodeURIComponent(date));
    }


    function deleteTask(taskID) {
        var xhr = new XMLHttpRequest();

        xhr.open("POST", "/project_UTS_lab/remove_task.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
  
                } else {
                    alert("Error: " + xhr.statusText);
                }
            }
        };

        xhr.send("delete=" + encodeURIComponent(taskID));
    }

    function deleteList(listID) {
        var xhr = new XMLHttpRequest();


        xhr.open("POST", "/project_UTS_lab/remove_list.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
  

                } else {
                    alert("Error: " + xhr.statusText);
                }
            }
        };

        xhr.send("delete=" + encodeURIComponent(listID));
    }

    function showDiv(divId) {

        if(divId == 0){
            document.getElementById('emailDiv').classList.add('hidden');
            document.getElementById('usernameDiv').classList.add('hidden');
            document.getElementById('passwordHref').classList.add('hidden');

            document.getElementById('newPasswordDiv').classList.remove('hidden');
            document.getElementById('OldpasswordDiv').classList.remove('hidden');
            document.getElementById('UserHref').classList.remove('hidden');

        }

        if(divId == 1){
            document.getElementById('emailDiv').classList.remove('hidden');
            document.getElementById('usernameDiv').classList.remove('hidden');
            document.getElementById('passwordHref').classList.remove('hidden');

            document.getElementById('newPasswordDiv').classList.add('hidden');
            document.getElementById('OldpasswordDiv').classList.add('hidden');
            document.getElementById('UserHref').classList.add('hidden');
        }

    }

    document.addEventListener('DOMContentLoaded', function() {
            var errorMessage = "<?php echo $error_message; ?>";
            if (errorMessage) {
                alert(errorMessage);
            }
        });

</script>
</body>
<style>
     .hidden {
        display: none;
    }

    html{
        overflow: hidden;
    }
    .nav-item{
        font-size: 1.3rem;
        margin: 0 0 1vh 0;
    }

    textarea:focus{
        box-shadow: none!important;
    }

    textarea{
        resize: none!important;
        border: none !important;  
        outline: none;
    }
    
    .todo-container .todo-list{
        margin-left: 1vw;
    }

    .sidebar-option, .container {
    overflow-y: scroll; /* Enable vertical scrolling */
    width: 100%;
    }

    .sidebar-option::-webkit-scrollbar, .container::-webkit-scrollbar {
        width: 8px; /* Set the width of the scrollbar */
    }

    .sidebar-option::-webkit-scrollbar-thumb, .container::-webkit-scrollbar-thumb {
        background-color: #888;
        border-radius: 12px;
    }

    .sidebar-option::-webkit-scrollbar-thumb:hover, .container::-webkit-scrollbar-thumb:hover {
        background-color: #555;
    }

    .sidebar-option::-webkit-scrollbar-track, .container::-webkit-scrollbar-track {
        background: transparent;
        margin-right: 0;
        padding-right: 0;
    }


    .sidebar .nav-link:active {
    color: #ffd700; 
    }

    .sidebar .nav-link:hover {
    background-color: #444; 
    }

    main .container {
        background-color: #f8f9fa; 
        color: #212529; 
        border: 1px solid #ddd; 
    }

    .todo-list a {
     font-weight: bold; 
    }

    .todo-list a:hover {
    color: #007bff; 
    }

    .todo-list .due-date {
    color: #ff6666; 
    }

    .todo-list button {
    color: #f8f9fa; 
    }

    .check-icon {
    margin-left: 2rem; 
    }

    .task-text {
        flex-grow: 1;
        margin-left: 3rem;
    }


    .custom-checkbox {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid #000;
        border-radius: 3px;
        position: relative;
        cursor: pointer;
        transition: background-color 0.2s ease, box-shadow 0.2s ease;
    }

    .custom-checkbox:hover {
        background-color: #28a745;
    }

    .custom-checkbox.checked {
        background-color: #28a745; 
    }

    .custom-checkbox.checked::after {
        display: none;
    }


    .custom-checkbox:hover::after {
        opacity: 0.2;
        border-color: rgba(0, 0, 0, 0.2);
    }

    .accordion-item, .accordion-button{
        background-color: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }


    .modal-backdrop {
        z-index: 1040 !important; /* Ensure backdrop appears beneath modal */
    }

    @media (max-width: 750px) {
        .form-select {
            width: 80vw !important; /* Lebar lebih besar pada layar kecil */
        }
    }


</style>
</html>
