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

/*********************
 *     Functions
 *********************/
function drawChart(data, labels = null, title = '', type = 'line', backgroundColor = null, borderColor = null) {

    //Assign default colors
    (backgroundColor === null) ? backgroundColor = DEFAULT_BACKGROUND_COLOR : backgroundColor = backgroundColor;
    (borderColor === null) ? borderColor = DEFAULT_BORDER_COLOR : borderColor = borderColor;

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
                    borderWidth: DEFAULT_BORDER_WIDTH,
                    tension: DEFAULT_TENSION,
                    hoverOffset: DEFAULT_OVER_OFFSET,
                }]
            },
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: title
                    },
                    subtitle: {
                        display: false,
                        text: ''
                    },
                    legend: {
                        display: true,
                        labels: {
                            color: 'rgb(0, 0, 0)'
                        }
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