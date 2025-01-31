function updateLineargauge(id, args, host, service) {
    
    const dashlet = document.querySelector(`#dashlet-${id}`);
    const dashletTitle = document.querySelector(`#dashletcontainer-${id} .dashlettopbox .dashlettitle`);
    // Check if dashlet exists
    if (!dashlet) {
        console.error(`Dashlet with id "dashlet-${id}" not found.`);
        return;
    }

    // Update gauge text
    dashlet.querySelector('.gaugetext h2').innerHTML = args['label'];

    // Update title with host, service, and label
    if (dashletTitle) {
        dashletTitle.innerHTML += ` - ${host} - ${service} - ${args['label']}`;
    }

    // Call helper to update gauge values
    setval(`#dashlet-${id}`, args['current'], args['warn'], args['crit'], args['max'], args['uom']);

      // Observe the gauge container for size changes
      const gaugeContainer = dashlet.querySelector('.gauge-container');
      if (gaugeContainer) {
          const resizeObserver = new ResizeObserver(() => {
              setval(`#dashlet-${id}`, args['current'], args['warn'], args['crit'], args['max'], args['uom']);
          });
          resizeObserver.observe(gaugeContainer);
      }
}

function setval(id, num, warn, crit, max, unit) {
    console.log("setvalhelper called with:", id, num, warn, crit, max, unit);

    const gaugedashlet = document.querySelector(id);
    if (!gaugedashlet) {
        console.error(`Gauge dashlet with id "${id}" not found.`);
        return;
    }

    const gaugeContainer = gaugedashlet.querySelector('.gauge-container');
    const gaugePointer = gaugedashlet.querySelector('.gauge-pointer');
    const gaugeWidth = gaugeContainer.clientWidth;

    // Ensure proper range values for warn and crit
    if (parseInt(warn) > parseInt(crit)) {
        [warn, crit] = [crit, warn]; // Swap values
    }

    // Update text and unit
    const valueElement = gaugedashlet.querySelector('.gaugetext p');
    const span = valueElement.querySelector('span');
    if (span) {
        span.innerHTML = unit; // Update the unit
    } else {
        // If span doesn't exist, create it
        const newSpan = document.createElement('span');
        newSpan.innerHTML = unit;
        valueElement.appendChild(newSpan);
    }

    valueElement.innerHTML = `${num}<span>${unit}</span>`;
    valueElement.style.fontSize = `${2.5 - 0.25 * Math.max(0, num.toString().length - 4)}em`;


    // Calculate pointer position
    let pointerPosition;

    if (num >= crit) {
        // Critical zone (rightmost third of the gauge)
        pointerPosition = (0.33 / 2) * gaugeWidth; // Middle of the critical zone

    } else if (num >= warn) {
        // Warning zone (middle third of the gauge)
        pointerPosition = (0.66 / 2 + 0.33) * gaugeWidth; // Middle of the warning zone
       
    } else {
        // OK zone (leftmost third of the gauge)
        pointerPosition = (0.34 / 2 + 0.66) * gaugeWidth; // Middle of the ok zone
    }

    console.log(gaugeWidth);
    // Ensure the pointer stays within the bounds of the gauge
    const pointerWidth = 20; // Width of the pointer
    const maxPointerPosition = gaugeWidth - pointerWidth / 2;
    const minPointerPosition = pointerWidth / 2;
    const clampedPointerPosition = Math.max(minPointerPosition, Math.min(maxPointerPosition, pointerPosition));

    // Update the pointer position
    gaugePointer.style.left = `${clampedPointerPosition}px`;

    // Dim non-active zones
    const criticalZone = gaugedashlet.querySelector('.gauge-section.critical');
    const warningZone = gaugedashlet.querySelector('.gauge-section.warning');
    const okZone = gaugedashlet.querySelector('.gauge-section.ok');

    if (num >= crit) {
        // Highlight critical zone, dim others
        criticalZone.classList.remove('dimmed');
        warningZone.classList.add('dimmed');
        okZone.classList.add('dimmed');
    } else if (num >= warn) {
        // Highlight warning zone, dim others
        criticalZone.classList.add('dimmed');
        warningZone.classList.remove('dimmed');
        okZone.classList.add('dimmed');
    } else {
        // Highlight OK zone, dim others
        criticalZone.classList.add('dimmed');
        warningZone.classList.add('dimmed');
        okZone.classList.remove('dimmed');
    }


}
