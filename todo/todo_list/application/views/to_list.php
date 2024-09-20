<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List</title>
    <!-- Include Bootstrap CSS for styling -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Additional styling for file thumbnails */
        .file-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">To-Do List</h2>

        <table class="table table-bordered table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Description</th>
                    <th>Date</th>
                    <th>Files</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                <tr>
                    <td><?= $task->id; ?></td>
                    <td><?= $task->description; ?></td>
                    <td><?= $task->date; ?></td>
                    <td>
                        <?php if ($task->files): ?>
                            <!-- Check if the file is an image to display a thumbnail -->
                            <?php 
                            $file_ext = pathinfo($task->files, PATHINFO_EXTENSION);
                            $image_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                            if (in_array(strtolower($file_ext), $image_extensions)): ?>
                                <img src="<?= base_url('uploads/' . $task->files); ?>" class="file-thumbnail" alt="File Thumbnail">
                            <?php else: ?>
                                <a href="<?= base_url('uploads/' . $task->files); ?>" target="_blank"><?= $task->files; ?></a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= site_url('todo/update/' . $task->id); ?>" class="btn btn-sm btn-primary">Edit</a>
                        <a href="<?= site_url('todo/delete/' . $task->id); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Include Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
