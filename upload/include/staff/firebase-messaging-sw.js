importScripts('https://www.gstatic.com/firebasejs/7.8.2/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/7.8.2/firebase-messaging.js');
// For an optimal experience using Cloud Messaging, also add the Firebase SDK for Analytics.
importScripts('https://www.gstatic.com/firebasejs/7.8.2/firebase-analytics.js');

// Initialize the Firebase app in the service worker by passing in the
// messagingSenderId.
firebase.initializeApp({
    'messagingSenderId': '546763358671'
});

// Retrieve an instance of Firebase Messaging so that it can handle background
// messages.
const messaging = firebase.messaging();

messaging.setBackgroundMessageHandler(function(payload) {
    // Customize notification here
    console.log(payload);
    var data = JSON.parse(payload.data.notification.replace(/(^\"|\"+$)/g, ""));
    const notificationTitle = data.title;
    const notificationOptions = {
        body: data.body,
        icon: data.icon
    };

    return self.registration.showNotification(notificationTitle,
        notificationOptions);
});