<?php

class M_todo extends CI_Model
{
    public function insert($data)
    {
        // Insert the to-do item into the 'todos' table
        $this->db->insert('tb_to_list', $data);
        return $this->db->insert_id(); // Return the ID of the inserted record
    }

    public function insert_files($todo_id, $files)
    {
        foreach ($files as $file) {
            $file_data = [
                'todo_id' => $todo_id,
                'file_name' => $file
            ];
            // Insert file record into 'todo_files' table
            $this->db->insert('tb_todo_files', $file_data);
        }
    }

    public function getAllTasks()
    {
        $query= $this->db->query("select todo.id,todo.date,todo.description,fs.file_name as files ,fs.id file_id
        from tb_to_list as todo 
        inner join tb_todo_files as fs on fs.todo_id=todo.id");
        return $query->result();
    }

    public function deleteTask($id)
    {
        $this->db->delete('tb_to_list', ['id' => $id]);
    }

    public function getTaskById($id)
    {
        $query= $this->db->query("select todo.id,todo.date,todo.description,fs.file_name as files ,fs.id file_id
        from tb_to_list as todo 
        inner join tb_todo_files as fs on fs.todo_id=todo.id
        where todo.id=$id");
        return $query->result();

    }

    public function updateTask($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update('tb_to_list', $data);
    }
}
