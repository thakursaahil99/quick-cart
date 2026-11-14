/**
 * QuickCart_SalesDashboard - hover layer for the server-rendered charts.
 *
 * Line chart: a crosshair snaps to the nearest day (pointer or arrow keys) and one
 * tooltip shows that day's values. Bars, columns and segments: each mark is its own
 * hit target (hover or keyboard focus). Tooltips only repeat what the axes, legend and
 * table view already show; all text is inserted with textContent.
 */
define([], function () {
    'use strict';

    /**
     * @param {HTMLElement} tooltip
     * @param {{title: String, value: String, note: String}} data
     */
    function fill(tooltip, data) {
        var value = document.createElement('strong'),
            title = document.createElement('span'),
            note;

        tooltip.textContent = '';
        value.textContent = data.value || '';
        title.textContent = data.title || '';
        tooltip.appendChild(value);
        tooltip.appendChild(title);
        if (data.note) {
            note = document.createElement('span');
            note.className = 'qc-tooltip__note';
            note.textContent = data.note;
            tooltip.appendChild(note);
        }
        tooltip.hidden = false;
    }

    /**
     * Position the tooltip above (x, y) in the container's pixel space, kept inside it.
     *
     * @param {HTMLElement} tooltip
     * @param {HTMLElement} container
     * @param {Number} x
     * @param {Number} y
     */
    function place(tooltip, container, x, y) {
        var width = tooltip.offsetWidth,
            left = Math.min(Math.max(x - width / 2, 0), container.clientWidth - width);

        tooltip.style.left = left + 'px';
        tooltip.style.top = Math.max(y - tooltip.offsetHeight - 12, 0) + 'px';
    }

    /**
     * @param {HTMLElement} chart
     */
    function lineChart(chart) {
        var points = JSON.parse(chart.getAttribute('data-points') || '[]'),
            svg = chart.querySelector('svg'),
            hit = chart.querySelector('[data-role="hit"]'),
            crosshair = chart.querySelector('[data-role="crosshair"]'),
            dot = chart.querySelector('[data-role="dot"]'),
            tooltip = chart.querySelector('[data-role="tooltip"]'),
            viewWidth = svg.viewBox.baseVal.width,
            current = points.length - 1;

        if (!points.length || !hit) {
            return;
        }

        function show(index) {
            var point = points[index],
                scale = svg.clientWidth / viewWidth;

            current = index;
            crosshair.setAttribute('x1', point.x);
            crosshair.setAttribute('x2', point.x);
            dot.setAttribute('cx', point.x);
            dot.setAttribute('cy', point.y);
            chart.classList.add('is-active');
            fill(tooltip, point);
            place(tooltip, chart, point.x * scale, point.y * scale);
        }

        function hide() {
            chart.classList.remove('is-active');
            tooltip.hidden = true;
        }

        function nearest(event) {
            var pt = svg.createSVGPoint(),
                local,
                best = 0;

            pt.x = event.clientX;
            pt.y = event.clientY;
            local = pt.matrixTransform(svg.getScreenCTM().inverse());
            points.forEach(function (point, index) {
                if (Math.abs(point.x - local.x) < Math.abs(points[best].x - local.x)) {
                    best = index;
                }
            });

            return best;
        }

        hit.addEventListener('pointermove', function (event) {
            show(nearest(event));
        });
        hit.addEventListener('pointerleave', hide);
        chart.addEventListener('focus', function () {
            show(current);
        });
        chart.addEventListener('blur', hide);
        chart.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                event.preventDefault();
                show(Math.min(points.length - 1, Math.max(0, current + (event.key === 'ArrowLeft' ? -1 : 1))));
            } else if (event.key === 'Escape') {
                hide();
            }
        });
    }

    /**
     * @param {HTMLElement} chart
     */
    function markChart(chart) {
        var tooltip = chart.querySelector('[data-role="tooltip"]');

        if (!tooltip) {
            return;
        }
        chart.querySelectorAll('[data-tip]').forEach(function (mark) {
            var data = JSON.parse(mark.getAttribute('data-tip'));

            function show() {
                var box = mark.getBoundingClientRect(),
                    frame = chart.getBoundingClientRect();

                mark.classList.add('is-active');
                fill(tooltip, data);
                place(tooltip, chart, box.left - frame.left + box.width / 2, box.top - frame.top);
            }

            function hide() {
                mark.classList.remove('is-active');
                tooltip.hidden = true;
            }

            mark.addEventListener('pointerenter', show);
            mark.addEventListener('focus', show);
            mark.addEventListener('pointerleave', hide);
            mark.addEventListener('blur', hide);
        });
    }

    return function (config, root) {
        root.querySelectorAll('[data-role="line-chart"]').forEach(lineChart);
        root.querySelectorAll('[data-role="bar-chart"]').forEach(markChart);
    };
});
