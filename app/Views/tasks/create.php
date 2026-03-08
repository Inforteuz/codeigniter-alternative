<?php clone $this; $this->extend('layouts/default'); ?>

<?php $this->section('title'); ?>Create Task<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white">
                <h4 class="mb-0">Create New Task</h4>
            </div>
            <div class="card-body p-4">
                <form action="/tasks" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Task Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. Finish the quarterly report">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Description (Optional)</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Add more details..."></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="/tasks" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>
