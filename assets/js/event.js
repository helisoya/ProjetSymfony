$(document).ready(()=> {
    $('#button-submit').on('click',(e) => {
        e.preventDefault()
        $.ajax({
            url: "/event/search",
            type: "get",
            data: {
                title: $('#search-title').val(),
                description: $('#search-description').val(),
                creatorId: $('#search-creator').val(),
            },
            success: function (response) {
                console.log(response)
            },
            error: function (xhr) {
                console.log(xhr)
            }
        });
        document.getElementById('search-title')

    });
});
