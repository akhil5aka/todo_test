<?php

class Todo extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['form', 'url']);
        $this->load->library(['form_validation', 'upload']);
        $this->load->database(); // Load database
        $this->load->model('M_todo'); // Load model for database interaction
    }

    public function create()
    {
        // Set validation rules for the description field
        $this->form_validation->set_rules('description', 'Description', 'required');
    
        if ($this->form_validation->run() == FALSE) {
            // Reload the form with validation errors
            $this->load->view('todo');
        } else {
            // Retrieve data from $_POST
            $description = $this->input->post('description');
            $date = date('Y-m-d');
    
            // Prepare data to insert into the database
            $data = [
                'description' => $description,
                'date' => $date, // Store the current date
            ];
    
            // Insert description into the database and get the inserted ID
            $todo_id = $this->M_todo->insert($data);
    
            // Check if any files were uploaded
            if (!empty($_FILES['files']['name'][0])) {
                $uploadedFiles = [];
    
                // Configuration for file uploads
                $config['upload_path'] = './uploads/';
                $config['allowed_types'] = '*'; // Allow all types for now
                $config['overwrite'] = FALSE;
                $this->upload->initialize($config);
                
                // var_dump($_FILES);

                foreach ($_FILES['files']['name'] as $key => $file) {
                    // Prepare the file array for each file
                    $_FILES['file']['name'] = $_FILES['files']['name'][$key];
                    $_FILES['file']['type'] = $_FILES['files']['type'][$key];
                    $_FILES['file']['tmp_name'] = $_FILES['files']['tmp_name'][$key];
                    $_FILES['file']['error'] = $_FILES['files']['error'][$key];
                    $_FILES['file']['size'] = $_FILES['files']['size'][$key];
    
                    // Attempt to upload the file
                    if ($this->upload->do_upload('file')) {
                        $uploadData = $this->upload->data();
                        // Save the file name to the array
                        $uploadedFiles[] = $uploadData['file_name'];
                    } else {
                        // Log any upload errors and display them
                        $uploadError = $this->upload->display_errors();
                        log_message('error', $uploadError);
                        echo $uploadError;
                        return;
                    }
                }
    
                // If files were uploaded, insert the file paths into the database
                if (!empty($uploadedFiles)) {
                    $this->M_todo->insert_files($todo_id, $uploadedFiles);
                }
            }
    
            // Redirect to the success page
            redirect('todo/success');
        }
    }
    
    public function success()
    {
        // Fetch all tasks from the database using the M_todo model
        $data['tasks'] = $this->M_todo->getAllTasks();
        
        // Load the success view and pass the tasks data
        $this->load->view('to_list', $data);
    }

    public function delete($id)
    {
        // Delete the task using the M_todo model
        $this->M_todo->deleteTask($id);
        // Redirect to the success page
        redirect('todo/success');
    }

    public function update($id)
    {
        // Set validation rules for the description field
        $this->form_validation->set_rules('description', 'Description', 'required');

        if ($this->form_validation->run() == FALSE) {
            // Fetch the specific task by ID
            $data['task'] = $this->M_todo->getTaskById($id);
            // Load the edit view with the task data
            $this->load->view('todo_edit', $data);
        } else {
            // Prepare data to update
            $data = ['description' => $this->input->post('description')];
            // Update the task using the M_todo model
            $this->M_todo->updateTask($id, $data);
            // Redirect to the success page
            redirect('todo/success');
        }
    }
}
