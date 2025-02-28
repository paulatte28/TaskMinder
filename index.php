<?php
session_start(); 
include 'database/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: views/login.php'); 
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TaskMinder</title>
  <link rel="icon" href="task.png" type="image/x-icon">
  <link href="../statics/css/bootstrap.min.css" rel="stylesheet">
  <script src="../statics/js/bootstrap.js"></script>
  <style>
    :root {
      --primary-color: #2c3e50;
      --secondary-color: #3498db;
    }

    .todo-card {
      transition: transform 0.2s;
      border-left: 4px solid;
    }

    .todo-card:hover {
      transform: translateY(-3px);
    }

    .priority-high {
      border-color: #e74c3c;
    }

    .priority-medium {
      border-color: #f1c40f;
    }

    .priority-low {
      border-color: #2ecc71;
    }

    .progress-bar {
      height: 8px;
      border-radius: 4px;
    }

    .due-date-badge {
      font-size: 0.8rem;
      padding: 0.25rem 0.5rem;
    }
  </style>
</head>

<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h1 class="display-6 fw-bold text-primary">TaskMinder</h1>
          <div>
            <a href="views/add_todo.php" class="btn btn-primary btn-sm">+ New Task</a>
          </div>
          <a href="handlers/logout_handler.php" class="btn btn-danger btn-sm">Logout</a>
        </div>

        <form method="GET" class="mb-4">
          <div class="input-group">
            <input type="text" name="search" class="form-control" 
            placeholder="Search tasks..." value="<?= isset($_GET['search']) 
            ? htmlspecialchars($_GET['search']) : '' ?>">
            <button class="btn btn-primary" type="submit">Search</button>
          </div>
        </form>

        <div class="row mb-4 g-3">
          <div class="col-md-4">
            <div class="card p-3 bg-white">
              <small class="text-muted">Total Tasks</small>
              <h4 class="mb-0">
                <?php
                $totalTasksResult = $conn->query("SELECT COUNT(*) FROM todo");
                echo $totalTasksResult ? $totalTasksResult->fetch_row()[0] : 0; 
                ?>
              </h4>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card p-3 bg-white">
              <small class="text-muted">Ongoing Tasks</small>
              <h4 class="mb-0">
                <?php
                $ongoingCountResult = $conn->query("SELECT COUNT(*) FROM todo WHERE status = 0");
                echo $ongoingCountResult ? $ongoingCountResult->fetch_row()[0] : 0; 
                ?>
              </h4>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card p-3 bg-white">
              <small class="text-muted">Done Tasks</small>
              <h4 class="mb-0">
                <?php
                $doneCountResult = $conn->query("SELECT COUNT(*) FROM todo WHERE status = 1");
                echo $doneCountResult ? $doneCountResult->fetch_row()[0] : 0; 
                ?>
              </h4>
            </div>
          </div>
        </div>

        <?php
        $searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

        $result = $conn->query("
          SELECT t.*, 
            COUNT(s.id) AS total_subtasks,
            SUM(s.completed = 1) AS completed_subtasks
          FROM todo t
          LEFT JOIN subtasks s ON t.id = s.todo_id
          WHERE t.title LIKE '%$searchTerm%' OR t.description LIKE '%$searchTerm%'
          GROUP BY t.id
          ORDER BY 
            CASE priority
              WHEN 'high' THEN 1
              WHEN 'medium' THEN 2
              ELSE 3
            END,
            due_date ASC
        ");

        if ($result === false) {
            echo "<div class='alert alert-danger'>Query error: " . $conn->error . "</div>";
        } else {
            if ($result->num_rows > 0): ?>
                <div class="card shadow-sm">
                  <div class="card-body p-0">
                    <?php while($todo = $result->fetch_assoc()): ?>
                      <div class="todo-card p-4 border-bottom <?= 'priority-'.$todo['priority'] ?>">
                        <div class="d-flex justify-content-between align-items-start">
                          <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-2">
                              <h5 class="fw-bold mb-0"><?= htmlspecialchars($todo['title']) ?></h5>
                              <?php if($todo['due_date']): ?>
                                <span class="due-date-badge badge <?= 
                                  (strtotime($todo['due_date']) < time()) ? 'bg-danger' : 'bg-secondary' 
                                ?>">
                                  <?= date('M j', strtotime($todo['due_date'])) ?>
                                </span>
                              <?php endif; ?>
                            </div>

                            <p class="text-muted mb-2"><?= htmlspecialchars($todo['description']) ?></p>

                            <?php if($todo['total_subtasks'] > 0): ?>
                              <div class="mb-3">
                                <div class="progress">
                                  <div class="progress-bar bg-success" 
                                       style="width: <?= ($todo['completed_subtasks'] / $todo['total_subtasks']) * 100 ?>%">
                                  </div>
                                </div>
                                <small class="text-muted">
                                  <?= $todo['completed_subtasks'] ?>/<?= $todo['total_subtasks'] ?> subtasks
                                </small>
                              </div>
                            <?php endif; ?>
                            <div class="d-flex gap-2 mb-3">
                              <span class="badge bg-primary"><?= ucfirst($todo['priority']) ?></span>
                              <?php if(isset($todo['category'])): ?>
                                <span class="badge bg-secondary"><?= htmlspecialchars($todo['category']) ?></span>
                              <?php endif; ?>
                            </div>
                          </div>

                          <div class="btn-group-vertical">
                            <a href="views/update_todo.php?id=<?= $todo['id'] ?>" 
                               class="btn btn-sm btn-outline-secondary">Edit</a>
                            <a href="handlers/delete_todo_handler.php?id=<?= $todo['id'] ?>" 
                               class="btn btn-sm btn-outline-danger">Delete</a>
                          </div>
                        </div>
                      </div>
                    <?php endwhile; ?>
                  </div>
                </div>
            <?php else: ?>
                <div class='card text-center py-5'>
                  <p>No tasks found matching your search.</p>
                </div> 
            <?php endif;
        }
        ?>
      </div>
    </div>
  </div>

</body>

</html>
