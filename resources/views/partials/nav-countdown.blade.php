<?php
$navCurrentTime = new DateTime();
$navReleaseTime = new DateTime('Dec 16th 17:00', new DateTimeZone('UTC'));
$navShowTimer = $navCurrentTime < $navReleaseTime;
if($navShowTimer) {
    $navCountdownTime = date_diff($navReleaseTime, $navCurrentTime);
    $navCountdownSeconds = $navReleaseTime->format('U') - $navCurrentTime->format('U');
}
?>
@if($navShowTimer)
    <div class="right-0 text-10 py-8 px-30 absolute top-full bg-yellow-100 text-blue-600 -z-1"><span id="release-timer" data-release-timer="{{ $navCountdownSeconds }}">{{ $navCountdownTime->format('%d Days / %h Hrs / %i Mins Until Mint') }}</span></div>
    <script id="js-nav-countdown">
        var el = document.getElementById('release-timer');
        var timer = el.dataset.releaseTimer;
        if(timer > 0) {
            navCountDown();
        }

        function pad(n) {
            return (n < 10 ? "0" + n : n);
        }

        function navCountDown() {
            timer = timer - 1;

            if(timer > 0) {
                setTimeout(navCountDown, 1000);
            } else {
                el.parentElement.remove();
            }

            var days        = Math.floor(timer/24/60/60);
            var hoursLeft   = Math.floor((timer) - (days*86400));
            var hours       = Math.floor(hoursLeft/3600);
            var minutesLeft = Math.floor((hoursLeft) - (hours*3600));
            var minutes     = Math.floor(minutesLeft/60);
            var innerText = '';

            if(days > 0) {
                innerText += days + ' Days / ';
            }

            if(hoursLeft > 0) {
                innerText += pad(hours) + ' Hrs / ';
            }

            if(minutesLeft > 0) {
                innerText += pad(minutes) + ' Mins Until Mint';
            }

            el.innerText = innerText;
        }
    </script>
@endif
