(function ($) {
    "use strict";
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
    const tooltipList = [...tooltipTriggerList].map(el => new bootstrap.Tooltip(el, {
        html: true // This allows you to use <b>, <br>, etc.
    }))
    /**
     * Ajax csrf token setup
     */
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });

    $(".select2").select2();
    $(".select2-search-disable").select2({ minimumResultsForSearch: 1 / 0 });
    // $(".form-select").select2({ minimumResultsForSearch: 1 / 0 });

    /**
     * Image viewer modal
     */
    $(document).on("click", ".view-image", function () {
        const imageUrl = $(this).data("image-url");
        $("#modalImage").attr("src", imageUrl); // Set the image URL in the modal
        $("#viewImageModal").modal("show"); // Show the modal
    });

    /**
     * Spinner on submit button
     */
    $(document).on("submit", "#prevent-form", function () {
        let spinTag = "<i class='fas fa-spinner fa-spin mr-2'></i>";
        let text = " Please wait...";
        let buttonText = spinTag + text;
        $(".submit-button").prop("disabled", true).html(buttonText);
    });

    /**
     * Delete confirmation
     */
    $(document).on("click", ".delete-data", function (e) {
        e.preventDefault();
        let target = $(this).attr("data-id");
        Swal.fire({
            title: "Are you sure?",
            text: "You won't to delete this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!",
        }).then((result) => {
            if (result.isConfirmed) {
                $("#" + target).submit();
            }
        });
    });

    /**
     * Submit confirmation
     */
    $(document).on("click", ".confirm-submit", function (e) {
        e.preventDefault();

        const url = $(this).attr("href"); // capture link

        Swal.fire({
            title: "Are you sure?",
            text: "You want to do this action!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, do it!",
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url; // ✅ execute link
            }
        });
    });


})(jQuery);
