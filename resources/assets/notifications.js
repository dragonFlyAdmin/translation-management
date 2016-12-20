import VueNotifications from 'vue-notifications'
import noty from 'noty'; // jQuery was included before this JS file

VueNotifications.config.timeout = 7000;

function triggerNotification(arg){
    return noty({
        text: arg.message,
        type: arg.type,
        theme: 'bootstrapTheme',
        layout: 'topRight',
        timeout: arg.timeout,
        callback: {
            afterClose: arg.cb,
        },
        animation: {
            open: 'animated zoomIn', // Animate.css class names
            close: 'animated zoomOut', // Animate.css class names
            easing: '', // unavailable - no need
            speed: 500 // unavailable - no need
        }
    })
}

export default {
    notificationOptions: {
        success: triggerNotification,
        error: triggerNotification,
        info: triggerNotification,
        warn: triggerNotification
    },
    VueNotifications: VueNotifications
};