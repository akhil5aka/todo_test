<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit To-Do</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Edit To-Do</h2>

        <?php if (validation_errors()): ?>
            <div class="alert alert-danger">
                <?= validation_errors(); ?>
            </div>
        <?php endif; ?>

        <form action="<?= site_url('todo/update/' . $task[0]->id); ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="description">Description</label>
                <input type="text" class="form-control" id="description" name="description" value="<?= set_value('description', $task[0]->description); ?>">
            </div>

            <!-- Display Existing Files -->
            <div class="form-group">
                <label>Current Files:</label>
                <div>
                    <?php if (!empty($task)): ?>
                        <?php foreach ($task as $t): ?>
                            <?php if ($t->files): ?>
                                <?php 
                                $file_ext = pathinfo($t->files, PATHINFO_EXTENSION);
                                $image_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                                if (in_array(strtolower($file_ext), $image_extensions)): ?>
                                    <img src="<?= base_url('uploads/' . $t->files); ?>" alt="File Thumbnail" class="img-thumbnail" style="width: 100px; height: 100px;">
                                <?php else: ?>
                                    <a href="<?= base_url('uploads/' . $t->files); ?>" target="_blank"><?= $t->files; ?></a>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No files uploaded yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upload New Files (Optional) -->
            <div class="form-group">
                <label for="files">Upload New Files (Optional)</label>
                <input type="file" class="form-control-file" id="files" name="files[]" multiple>
            </div>

            <button type="submit" class="btn btn-success">Update To-Do</button>
            <a href="<?= site_url('todo/success'); ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
