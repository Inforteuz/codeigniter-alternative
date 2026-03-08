<?php

namespace App\Controllers;

use System\BaseController;
use App\Models\TaskModel;
use App\Core\Auth\Auth;

class TaskController extends BaseController
{
    protected $taskModel;

    public function __construct()
    {
        parent::__construct();
        $this->taskModel = new TaskModel();
    }

    public function index()
    {
        $userId = Auth::id();
        $tasks = $this->taskModel->where('user_id', $userId)->orderBy('created_at', 'DESC')->get();

        $this->view('tasks/index', ['tasks' => $tasks]);
    }

    public function create()
    {
        $this->view('tasks/create');
    }

    public function store()
    {
        // Validate
        $title = $this->getPost('title');
        $description = $this->getPost('description');

        if (empty($title)) {
            $this->flashInput();
            $this->with('error', 'Title is required.')->redirect()->to('tasks/create');
            return;
        }

        $this->taskModel->insertModel([
            'user_id' => Auth::id(),
            'title' => $title,
            'description' => $description,
            'status' => 'pending'
        ]);

        $this->with('success', 'Task created!')->redirect()->to('tasks');
    }

    public function complete($id)
    {
        // Verify ownership
        $task = $this->taskModel->find($id);
        if ($task && $task['user_id'] == Auth::id()) {
            $this->taskModel->updateModel($id, ['status' => 'completed']);
            $this->with('success', 'Task marked as completed!');
        } else {
            $this->with('error', 'Unauthorized action.');
        }

        $this->redirect()->to('tasks');
    }

    public function delete($id)
    {
        $task = $this->taskModel->find($id);
        if ($task && $task['user_id'] == Auth::id()) {
            $this->taskModel->delete($id);
            $this->with('success', 'Task deleted!');
        } else {
            $this->with('error', 'Unauthorized action.');
        }

        $this->redirect()->to('tasks');
    }
}
