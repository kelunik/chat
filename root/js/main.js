if (window.top != window.self) {
    alert("For security reasons, framing is not allowed!");
    window.top.location.href = window.location.href;
} else {
    var loadError = document.getElementById("load-error");

    if (loadError) {
        loadError.parentNode.removeChild(loadError);
    }

    require("./chat.js");
}
