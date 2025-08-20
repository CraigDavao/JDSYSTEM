document.addEventListener("DOMContentLoaded", () => {
    const dropdownParents = document.querySelectorAll(".has-dropdown");
    
    dropdownParents.forEach(parent => {
        parent.addEventListener("mouseenter", () => {
            parent.classList.add("open");
            document.body.classList.add("no-scroll");
        });
        
        parent.addEventListener("mouseleave", () => {
            parent.classList.remove("open");
            document.body.classList.remove("no-scroll");
        });
    });
    
    // Close dropdown when clicking outside
    document.addEventListener("click", (e) => {
        if (!e.target.closest('.has-dropdown')) {
            dropdownParents.forEach(parent => {
                parent.classList.remove("open");
            });
            document.body.classList.remove("no-scroll");
        }
    });
});