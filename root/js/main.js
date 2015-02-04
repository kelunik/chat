if (window.top != window.self) {
    alert("For security reasons, framing is not allowed!");
    window.top.location.href = window.location.href;
} else {
    init(window, document, ActivityObserver, DataHandler, Formatter, Handlebars, Messages, NotificationCenter, Rooms, TemplateManager, user);
}
