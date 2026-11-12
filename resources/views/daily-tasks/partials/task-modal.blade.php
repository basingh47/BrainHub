<div id="task-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl">

        <h3 id="task-modal-title" class="text-lg font-semibold text-gray-900">Add task</h3>

    </div>
</div>

<script>
    // Get the modal and the button that opens it
    const taskModal = document.getElementById('task-modal');
    const openAddTaskModalButton = document.getElementById('openAddTaskModal');

    // When the user clicks the button, open the modal
    openAddTaskModalButton.addEventListener('click', () => {
        taskModal.classList.remove('hidden');
        taskModal.classList.add('flex');
    });

    // When the user clicks outside the modal content, close the modal
    taskModal.addEventListener('click', (event) => {
        if (event.target === taskModal) {
            taskModal.classList.remove('flex');
            taskModal.classList.add('hidden');
        }
    });
</script>
