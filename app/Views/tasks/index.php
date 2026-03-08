<?php clone $this; $this->extend('layouts/default'); ?>

<?php $this->section('title'); ?>My Tasks<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4 mt-3">
    <h2>My Tasks</h2>
    <a href="/tasks/create" class="btn btn-primary">+ New Task</a>
</div>

<?php if (empty($tasks)): ?>
    <div class="alert alert-info border-0 shadow-sm text-center p-5">
        <h4>No tasks found!</h4>
        <p class="text-muted">You haven't created any tasks yet.</p>
        <a href="/tasks/create" class="btn btn-outline-primary mt-3">Create your first task</a>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($tasks as $task): ?>
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100 <?= $task['status'] === 'completed' ? 'border-success' : 'border-primary' ?>">
                    <div class="card-body">
                        <h5 class="card-title <?= $task['status'] === 'completed' ? 'text-decoration-line-through text-muted' : '' ?>">
                            <?= htmlspecialchars($task['title']) ?>
                        </h5>
                        <p class="card-text text-muted small">
                            <?= nl2br(htmlspecialchars($task['description'])) ?>
                        </p>
                    </div>
                    <div class="card-footer bg-white border-0 d-flex justify-content-between pb-3">
                        <span class="badge <?= $task['status'] === 'completed' ? 'bg-success' : 'bg-warning text-dark' ?>">
                            <?= ucfirst($task['status']) ?>
                        </span>
                        
                        <div class="btn-group btn-group-sm">
                            <?php if ($task['status'] !== 'completed'): ?>
                                <form action="/tasks/<?= $task['id'] ?>/complete" method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                    <button type="submit" class="btn btn-outline-success" title="Mark Complete">✓</button>
                                </form>
                            <?php endif; ?>
                            
                            <a href="/tasks/<?= $task['id'] ?>/delete" class="btn btn-outline-danger" onclick="return confirm('Delete this task?');" title="Delete">🗑</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php $this->endSection(); ?>
