/*********************
 *      Settings
 *********************/
const DEFAULT_BACKGROUND_COLOR = [
    'rgba(255,  99, 132, 0.2)',
    'rgba(255, 159,  64, 0.2)',
    'rgba(255, 205,  86, 0.2)',
    'rgba( 75, 192, 192, 0.2)',
    'rgba( 54, 162, 235, 0.2)',
    'rgba(153, 102, 255, 0.2)',
    'rgba(201, 203, 207, 0.2)'
]

const DEFAULT_BORDER_COLOR = [
    'rgb(255,  99, 132)',
    'rgb(255, 159,  64)',
    'rgb(255, 205,  86)',
    'rgb( 75, 192, 192)',
    'rgb( 54, 162, 235)',
    'rgb(153, 102, 255)',
    'rgb(201, 203, 207)'
]

const DEFAULT_BORDER_WIDTH = 1

const DEFAULT_TENSION = 0.1 // line

const DEFAULT_OVER_OFFSET = 20 // doughnut, pie AND polarArea

const SUPPORTED_TYPES = [
    'line',
    'bar',
    'doughnut',
    'pie',
    'polarArea'
]

const SUPPORTED_POINT_STYLES = [
    'circle',
    'cross',
    'crossRot',
    'dash',
    'line',
    'rect',
    'rectRounded',
    'rectRot',
    'star',
    'triangle'
]

const DEFAULT_ENABLE_ANIMATION = false;
const DEFAULT_SHOW_TITLE = false;
const DEFAULT_SHOW_SUBTITLE = false;

const DEFAULT_SHOW_LEGEND = false;
const DEFAULT_LEGEND_POSITION = 'top';

/*********************
 *     Functions
 *********************/
function drawChart(
    data,
    labels = null,
    title = '',
    type = 'line',
    tension = null,
    backgroundColor = null,
    borderColor = null,
    borderWidth = null,
    enableAnimation = null,
    showTitle = null,
    showSubTitle = null,
    showLegend = null,
    legendPosition = null
) {

    //Enable Elements
    (enableAnimation === null) ? enableAnimation = Boolean(DEFAULT_ENABLE_ANIMATION) : enableAnimation = Boolean(enableAnimation);
    (showTitle === null) ? showTitle = Boolean(DEFAULT_SHOW_TITLE) : showTitle = Boolean(showTitle);
    (showSubTitle === null) ? showSubTitle = Boolean(DEFAULT_SHOW_SUBTITLE) : showSubTitle = Boolean(showSubTitle);
    (showLegend === null) ? showLegend = Boolean(DEFAULT_SHOW_LEGEND) : showLegend = Boolean(showLegend);

    (tension === null) ? tension = DEFAULT_TENSION : tension = tension;

    //Assign default colors
    (backgroundColor === null) ? backgroundColor = DEFAULT_BACKGROUND_COLOR : backgroundColor = backgroundColor;
    (borderColor === null) ? borderColor = DEFAULT_BORDER_COLOR : borderColor = borderColor;
    (borderWidth === null) ? borderWidth = DEFAULT_BORDER_WIDTH : borderWidth = borderWidth;

    (legendPosition === null) ? legendPosition = DEFAULT_LEGEND_POSITION : legendPosition = legendPosition;


    if(!Array.isArray(data)) {
        //TODO: if is url, get data
    }

    if( SUPPORTED_TYPES.includes(type) && Array.isArray(data) ) {
        const config = {
            type: type,
            data: {
                labels: labels,
                datasets: [{
                    label: title,
                    data: data,
                    backgroundColor: backgroundColor,
                    borderColor: borderColor,
                    borderWidth: borderWidth,
                    tension: tension,
                    hoverOffset: DEFAULT_OVER_OFFSET,
                }]
            },
            options: {
                animation: enableAnimation,
                plugins: {
                    title: {
                        display: showTitle,
                        text: title
                    },
                    subtitle: {
                        display: showSubTitle,
                        text: ''
                    },
                    legend: {
                        display: showLegend,
                        position: legendPosition,
                    }
                }
            }
        };
        return config;

    } else {

        if(!Array.isArray(data)){
            alert('data is not valid...');
        }

        if( !SUPPORTED_TYPES.includes(type) ) {
            alert('Chart Type "' + type + '" is not supported...');
        }

    }

}
/*********************
 *   Theme colours
 *********************/
// Axis labels, grid lines and fonts follow the admin theme, and follow it again when the viewer toggles dark mode.
function applyThemeToCharts() {
    const css = getComputedStyle(document.documentElement);

    Chart.defaults.color = css.getPropertyValue('--bf-muted').trim();
    Chart.defaults.borderColor = css.getPropertyValue('--bf-border').trim();
    Chart.defaults.font.family = css.getPropertyValue('--bf-font').trim();

    Object.values(Chart.instances).forEach((chart) => {
        chart.options.scales && Object.values(chart.options.scales).forEach((scale) => {
            scale.ticks.color = Chart.defaults.color;
            scale.grid.color = Chart.defaults.borderColor;
            scale.border && (scale.border.color = Chart.defaults.borderColor);
            scale.title && (scale.title.color = Chart.defaults.color);
            scale.angleLines && (scale.angleLines.color = Chart.defaults.borderColor); // polarArea
            scale.pointLabels && (scale.pointLabels.color = Chart.defaults.color); // polarArea
        });
        chart.update();
    });
}

applyThemeToCharts();
document.addEventListener('bf-theme', applyThemeToCharts);
