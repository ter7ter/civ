var research = {
    drawResearchTree: function() {
        var container = $('.research-tree-container');
        var researchItems = $('.research-item');

        // Clear previous SVG and positioning
        container.find('svg').remove();
        researchItems.css({ 'left': '', 'top': '' });

        var itemWidth = 140; // from CSS
        var itemHeight = 50; // from CSS
        var horizontalPadding = 40; // padding between items horizontally
        var verticalPadding = 20; // padding between items vertically

        var maxDataLeft = 0;
        var maxDataTop = 0;
        var minDataLeft = Infinity;
        var minDataTop = Infinity;

        // Calculate max and min m_left and m_top from data attributes
        researchItems.each(function() {
            var itemLeft = parseInt($(this).data('m_left'));
            var itemTop = parseInt($(this).data('m_top'));
            if (itemLeft > maxDataLeft) maxDataLeft = itemLeft;
            if (itemTop > maxDataTop) maxDataTop = itemTop;
            if (itemLeft < minDataLeft) minDataLeft = itemLeft;
            if (itemTop < minDataTop) minDataTop = itemTop;
        });

        // Get actual dimensions of the container for scaling
        var containerWidth = container.innerWidth();
        var containerHeight = container.innerHeight(); // Use inner height to account for padding

        // Calculate the number of unique m_left and m_top values to determine columns and rows
        var uniqueMLefts = [];
        var uniqueMTops = [];
        researchItems.each(function() {
            var itemLeft = parseInt($(this).data('m_left'));
            var itemTop = parseInt($(this).data('m_top'));
            if (uniqueMLefts.indexOf(itemLeft) === -1) uniqueMLefts.push(itemLeft);
            if (uniqueMTops.indexOf(itemTop) === -1) uniqueMTops.push(itemTop);
        });
        uniqueMLefts.sort((a, b) => a - b);
        uniqueMTops.sort((a, b) => a - b);

        var numColumns = uniqueMLefts.length;
        var numRows = uniqueMTops.length;

        // Calculate effective spacing based on container dimensions
        var effectiveHorizontalSpacing = (containerWidth - (numColumns * itemWidth)) / (numColumns + 1);
        var effectiveVerticalSpacing = (containerHeight - (numRows * itemHeight)) / (numRows + 1);

        // Ensure minimum spacing
        if (effectiveHorizontalSpacing < horizontalPadding) effectiveHorizontalSpacing = horizontalPadding;
        if (effectiveVerticalSpacing < verticalPadding) effectiveVerticalSpacing = verticalPadding;

        // Create SVG for arrows
        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('width', containerWidth);
        svg.setAttribute('height', containerHeight);
        svg.style.position = 'absolute';
        svg.style.top = '0';
        svg.style.left = '0';
        svg.style.zIndex = '0';

        // Add arrowhead definition
        var defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        var marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
        marker.setAttribute('id', 'arrowhead');
        marker.setAttribute('markerWidth', '10');
        marker.setAttribute('markerHeight', '7');
        marker.setAttribute('refX', '10');
        marker.setAttribute('refY', '3.5');
        marker.setAttribute('orient', 'auto');
        var polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        polygon.setAttribute('points', '0 0, 10 3.5, 0 7');
        polygon.setAttribute('fill', '#6c757d');
        marker.appendChild(polygon);
        defs.appendChild(marker);
        svg.appendChild(defs);

        // Store positions for arrow drawing
        var itemPositions = {};

        // For each column (m_left), collect items and position them vertically
        uniqueMLefts.forEach(function(leftValue, columnIndex) {
            var columnItems = researchItems.filter(function() {
                return parseInt($(this).data('m_left')) === leftValue;
            });

            // Convert to array and sort items by m_top within the column
            var columnItemsArray = columnItems.toArray();
            columnItemsArray.sort(function(a, b) {
                return parseInt($(a).data('m_top')) - parseInt($(b).data('m_top'));
            });

            var numItemsInColumn = columnItemsArray.length;
            var beginningTop = 10; // No top margin
            var endTop = containerHeight - itemHeight - 50; // No bottom margin
            var totalHeight = endTop - beginningTop;

            var step = 0;
            if (numItemsInColumn > 1) {
                step = totalHeight / (numItemsInColumn - 1);
            }

            columnItemsArray.forEach(function(domItem, index) {
                var item = $(domItem);
                var itemId = item.data('id');

                // Horizontal positioning remains the same (even distribution)
                var newLeft = (columnIndex + 1) * effectiveHorizontalSpacing + columnIndex * itemWidth;

                // Vertical positioning: even distribution from beginningTop to endTop
                var newTop = beginningTop + index * step;

                // Apply calculated positions
                item.css({
                    'left': newLeft + 'px',
                    'top': newTop + 'px'
                });

                itemPositions[String(itemId)] = {
                    left: newLeft,
                    top: newTop,
                    width: itemWidth,
                    height: itemHeight
                };
            });
        });

        // Draw arrows
        researchItems.each(function() {
            var item = $(this);
            var itemId = item.data('id');
            var itemReqsData = item.data('req');
            if (!itemReqsData) {
                console.error('Missing req data for item', itemId);
                return;
            }

            // Parse requirements if it's a string
            var itemReqs = [];
            if (typeof itemReqsData === 'string') {
                try {
                    itemReqs = JSON.parse(itemReqsData);
                } catch (e) {
                    console.log('Error parsing requirements for item ' + itemId + ': ' + e);
                }
            } else if (Array.isArray(itemReqsData)) {
                itemReqs = itemReqsData;
            }

            if (itemReqs && itemReqs.length > 0) {
                itemReqs.forEach(function(reqId) {
                    // Ensure reqId is a string to match itemPositions keys
                    var reqIdStr = String(reqId);
                    var itemIdStr = String(itemId);

                    if (itemPositions[reqIdStr] && itemPositions[itemIdStr]) {
                        var from = itemPositions[reqIdStr];
                        var to = itemPositions[itemIdStr];

                        var x1 = from.left + from.width;
                        var y1 = from.top + from.height / 2;
                        var x2 = to.left;
                        var y2 = to.top + to.height / 2;

                        var line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                        line.setAttribute('x1', x1);
                        line.setAttribute('y1', y1);
                        line.setAttribute('x2', x2);
                        line.setAttribute('y2', y2);
                        line.setAttribute('stroke', '#6c757d');
                        line.setAttribute('stroke-width', '2');
                        line.setAttribute('marker-end', 'url(#arrowhead)');
                        svg.appendChild(line);
                    }
                });

            }
        });

        container.get(0).appendChild(svg);
    }
};

$(document).ready(function() {
    // Call drawResearchTree when the research window is shown
    // This event is triggered by js/events.js
    $('#research-window').on('researchWindowShown', function () {
        research.drawResearchTree();
    });
});
