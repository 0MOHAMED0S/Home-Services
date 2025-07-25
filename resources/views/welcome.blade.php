{{-- <!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Get FCM Token</title>
  <script type="module">
    import { initializeApp } from "https://www.gstatic.com/firebasejs/9.6.11/firebase-app.js";
    import { getMessaging, getToken } from "https://www.gstatic.com/firebasejs/9.6.11/firebase-messaging.js";

    const firebaseConfig = {
      apiKey: "AIzaSyBmHHmGCGIDvjoESjITK6P-Q2ZI0-LRiiE",
      authDomain: "homeservice-3bd86.firebaseapp.com",
      projectId: "homeservice-3bd86",
      storageBucket: "homeservice-3bd86.appspot.com",
      messagingSenderId: "1021870946240",
      appId: "1:1021870946240:web:8b00e53edbe81d27e27a25",
      measurementId: "G-LPFHQB5FHE"
    };

    const vapidKey = 'BNBi8dihT9GJKveV6qw-dfvFXUeFp6E_NOqzGBFhgje_HmIOM59ooOV7KzOMURDqO9UYp4m4DM91L5mNr0UTIh4';
    const app = initializeApp(firebaseConfig);
    const messaging = getMessaging(app);

    document.getElementById('subscribeBtn').addEventListener('click', async () => {
      try {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
          document.getElementById('token').textContent = "❌ Permission denied.";
          return;
        }

        const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
        const token = await getToken(messaging, {
          vapidKey: vapidKey,
          serviceWorkerRegistration: registration
        });

        if (token) {
          document.getElementById('token').textContent = token;
        } else {
          document.getElementById('token').textContent = "⚠️ Token not available.";
        }

      } catch (error) {
        document.getElementById('token').textContent = "❌ Error: " + error.message;
      }
    });
  </script>

</head>
<body>
  <h2>Click to Get FCM Token</h2>
  <button id="subscribeBtn">Get FCM Token</button>
  <pre id="token" style="white-space: pre-wrap; background: #f5f5f5; padding: 10px; border: 1px solid #ccc;"></pre>
</body>
</html> --}}

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>OneSignal Web Push Subscription</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- ✅ OneSignal SDK v16 -->
  <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>

  <script>
    window.OneSignalDeferred = window.OneSignalDeferred || [];
    OneSignalDeferred.push(async function (OneSignal) {
      // Initialize OneSignal
      await OneSignal.init({
        appId: "bfc39cb9-9a8f-4168-939f-bb2cf96afeca",
        promptOptions: {
          slidedown: {
            enabled: true,
            autoPrompt: false // Manual prompt
          }
        }
      });

      // Check subscription status
      const isPushSupported = await OneSignal.isPushSupported();
      const isSubscribed = await OneSignal.isPushNotificationsEnabled();

      console.log("Push Supported:", isPushSupported);
      console.log("Already Subscribed:", isSubscribed);

      if (isSubscribed) {
        const userId = await OneSignal.user.getId();
        console.log("Subscribed User ID:", userId);
      }
    });

    // Function to trigger the OneSignal subscription prompt
    function subscribeUserToNotifications() {
      window.OneSignalDeferred.push(async function (OneSignal) {
        try {
          await OneSignal.showSlidedownPrompt();
          const isEnabled = await OneSignal.isPushNotificationsEnabled();

          if (isEnabled) {
            const userId = await OneSignal.user.getId();
            alert("User subscribed! User ID: " + userId);
            console.log("Subscribed:", userId);
          } else {
            alert("User did not subscribe.");
            console.log("Not subscribed");
          }
        } catch (e) {
          console.error("Subscription error:", e);
        }
      });
    }
  </script>
</head>
<body>
  <h1>Subscribe to Notifications</h1>
  <p>Click the button below to subscribe to push notifications via OneSignal.</p>
  <button onclick="subscribeUserToNotifications()">Subscribe Now</button>
</body>
</html>
