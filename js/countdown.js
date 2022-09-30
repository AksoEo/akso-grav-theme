//! This file contains code for flap display countdowns.

class FlapDisplayCell {
    constructor () {
        this.node = document.createElement('div');
        this.node.className = 'flap-display-cell';

        // target digit index in [0, 9]
        this.digit = 0;
        // flip direction
        this.direction = -1;
        // max digit (mod)
        this.max = 10;
        // current digit. integer
        this.currentDigit = 0;
        // current digit offset. float in [0, 1[, indicates animation progress
        this.currentDigitOffset = 0;

        // the flap that lies below the top flap
        this.nextFlap = document.createElement('div');
        this.nextFlap.className = 'f-next-flap';
        this.nextFlapText = document.createElement('span');
        this.nextFlap.appendChild(this.nextFlapText);
        // the top flap
        this.topFlap = document.createElement('div');
        this.topFlap.className = 'f-moving-flap';
        this.topFlapText = document.createElement('span');
        this.topFlap.appendChild(this.topFlapText);
        // the bottom flap. does not move
        this.bottomFlap = document.createElement('div');
        this.bottomFlap.className = 'f-bottom-flap';
        this.bottomFlapText = document.createElement('span');
        this.bottomFlap.appendChild(this.bottomFlapText);

        this.updateDigitLabels();
        this.update(0);

        this.node.appendChild(this.nextFlap);
        this.node.appendChild(this.bottomFlap);
        this.node.appendChild(this.topFlap);
    }

    mod (x) {
        return ((x % this.max) + this.max) % this.max;
    }

    updateDigitLabels() {
        this.nextFlapText.textContent = this.mod(this.currentDigit + this.direction);
        this.bottomFlapText.textContent = this.currentDigit;
    }

    update (dt) {
        if (this.currentDigit !== this.digit || this.currentDigitOffset) {
            let speed = 3;
            if (this.mod(this.currentDigit + this.direction) !== this.digit) {
                speed = 8;
            }
            this.currentDigitOffset += dt * speed;
        }
        if (this.currentDigitOffset >= 1) {
            this.currentDigitOffset = 0;
            this.currentDigit = this.mod(this.currentDigit + this.direction);
            this.updateDigitLabels();
        }

        const easing = t => t < 0.5 ? (4 * t * t * t) : ((t - 1) * (2 * t - 2) * (2 * t - 2) + 1);

        let t = easing(this.currentDigitOffset);
        let rotation, filter;
        if (t < 0.5) {
            this.topFlapText.textContent = this.currentDigit;
            this.topFlap.classList.remove('is-back-side');
            rotation = 2 * t * -90;
            filter = `brightness(${1 - t})`;
        } else {
            this.topFlapText.textContent = this.mod(this.currentDigit + this.direction);
            this.topFlap.classList.add('is-back-side');
            const tt = (2 - 2 * t);
            rotation = tt * 90;
            filter = `brightness(${1 + tt})`;
        }
        this.topFlap.style.transform = `translateY(${t}px) rotateX(${rotation}deg)`;
        this.topFlap.style.filter = this.topFlap.style.webkitFilter = filter;
    }
}

function initLargeCountdown (countdown) {
    countdown.node.classList.add('is-flap-display');

    countdown.node.innerHTML = '';

    const dayDigits = [];

    const timeDigits = [];
    for (let i = 0; i < 6; i++) {
        const dc = new FlapDisplayCell();
        timeDigits.push(dc);
    }
    timeDigits[0].max = 3;
    timeDigits[2].max = timeDigits[4].max = 6;

    const createContainer = labelText => {
        const container = document.createElement('div');
        container.setAttribute('aria-hidden', 'true');
        container.className = 'flap-display-container';
        const digits = document.createElement('div');
        digits.className = 'container-digits';
        const label = document.createElement('div');
        label.className = 'container-label';
        label.textContent = labelText;
        container.appendChild(digits);
        container.appendChild(label);
        countdown.node.appendChild(container);
        return { container, digits };
    };

    const dayDigitsContainer = createContainer('Tagoj');
    const hourDigitsContainer = createContainer('Horoj');
    const minuteDigitsContainer = createContainer('Minutoj');
    const secondDigitsContainer = createContainer('Sekundoj');

    hourDigitsContainer.digits.appendChild(timeDigits[0].node);
    hourDigitsContainer.digits.appendChild(timeDigits[1].node);
    minuteDigitsContainer.digits.appendChild(timeDigits[2].node);
    minuteDigitsContainer.digits.appendChild(timeDigits[3].node);
    secondDigitsContainer.digits.appendChild(timeDigits[4].node);
    secondDigitsContainer.digits.appendChild(timeDigits[5].node);

    const targetTime = countdown.targetTime;

    countdown.update = () => {};
    countdown.animationUpdate = (dt) => {
        const interval = getTimeInterval(new Date(), targetTime); // global.js
        if (interval.invert) {
            // the countdown is done, clamp to zero
            interval.seconds = interval.minutes = interval.hours = interval.rawDays = 0;
        }

        let dayString = '' + interval.rawDays;
        dayString = '00'.substr(0, 2 - Math.min(2, dayString.length)) + dayString;
        const dayDigitCount = Math.max(dayString.length);
        while (dayDigits.length < dayDigitCount) {
            const dc = new FlapDisplayCell();
            dayDigits.unshift(dc);
            dayDigitsContainer.digits.insertBefore(dc.node, dayDigitsContainer.digits.firstChild);
        }
        /* // don't drop extra digits because people want to look at them apparently
        while (dayDigits.length > dayDigitCount) {
            const dc = dayDigits.shift();
            dayDigitsContainer.digits.removeChild(dc.node);
        }*/
        for (let i = 0; i < dayString.length; i++) {
            dayDigits[i].digit = +dayString[i];
        }

        timeDigits[0].digit = Math.floor(interval.hours / 10);
        timeDigits[1].digit = interval.hours % 10;
        timeDigits[2].digit = Math.floor(interval.minutes / 10);
        timeDigits[3].digit = interval.minutes % 10;
        timeDigits[4].digit = Math.floor(interval.seconds / 10);
        timeDigits[5].digit = interval.seconds % 10;

        for (const d of dayDigits) d.update(dt);
        for (const d of timeDigits) d.update(dt);
    };
}

function initLargeCountdowns () {
    if (!window.activeCountdowns) {
        // try again later after global.js has been loaded
        setTimeout(initLargeCountdowns, 100);
        return;
    }

    const animationSpeed = 1;

    const activeCountdowns = window.activeCountdowns;
    for (const countdown of activeCountdowns) {
        if (countdown.node.classList.contains('is-large')) initLargeCountdown(countdown);
    }

    let hasAnimatedCountdowns = !!activeCountdowns.find(x => x.animationUpdate);

    let lastTime = Date.now();
    const loop = () => {
        const dt = Math.min(1 / 30, Math.max(1 / 244, (Date.now() - lastTime) / 1000));
        lastTime = Date.now();
        for (const countdown of activeCountdowns) {
            if (countdown.animationUpdate) countdown.animationUpdate(dt * animationSpeed);
        }
        (requestAnimationFrame || ((k) => setTimeout(k, 16)))(loop);
    };
    if (hasAnimatedCountdowns) loop();
}
initLargeCountdowns();
