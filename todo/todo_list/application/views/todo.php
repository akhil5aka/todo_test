<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>"> <!-- Example for including CSS -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        input[type="text"], textarea, input[type="file"] {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
        }

        .error {
            color: red;
            font-size: 14px;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
        }

        button:hover {
            background-color: #218838;
        }

        input[readonly] {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Create To-Do Item</h2>

        <!-- Display validation errors -->
        <?php if (isset($validation)): ?>
            <div class="error">
                <?= $validation->listErrors() ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('index.php/Todo/create'); ?>" method="post" enctype="multipart/form-data">

        <div class="form-group">
            <label for="date">Date:</label>
            <input type="text" id="date" name="date" value="<?= date('Y-m-d') ?>" readonly>
        </div>

        <div class="form-group">
            <label for="description">Description:</label>
            <textarea name="description" id="description" rows="8" placeholder="Enter your task description here..." required></textarea>
        </div>

        <div class="form-group">
            <label for="files">Upload Files:</label>
            <input type="file" id="files" name="files[]" multiple>
        </div>

        <div class="form-group">
            <button type="submit">Submit</button>
        </div>

        </form>
    </div>
</body>
</html>
