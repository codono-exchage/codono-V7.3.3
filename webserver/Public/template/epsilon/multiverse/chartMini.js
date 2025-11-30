
const config = {
    1: {
        "symbol": "BINANCE:ETHUSDT",
        "locale": "en",
        "dateRange": "12M",
        "colorTheme": "dark",
        "isTransparent": false,
        "autosize": true,
        "largeChartUrl": ""
    },
    2: {
        "symbol": "BINANCE:BTCUSDT",
        "locale": "en",
        "dateRange": "12M",
        "colorTheme": "dark",
        "isTransparent": false,
        "autosize": true,
        "largeChartUrl": ""
    },
    3: {
        "symbol": "BINANCE:NEARUSDT",
        "locale": "en",
        "dateRange": "12M",
        "colorTheme": "dark",
        "isTransparent": false,
        "autosize": true,
        "largeChartUrl": ""
    },
    4: {
        "symbol": "BINANCE:XRPUSDT",
        "locale": "en",
        "dateRange": "12M",
        "colorTheme": "dark",
        "isTransparent": false,
        "autosize": true,
        "largeChartUrl": ""
    }
}

// trade view chart
function miniChart(containerId, configType) {
    const script = document.createElement("script");
    script.src =
      "https://s3.tradingview.com/external-embedding/embed-widget-mini-symbol-overview.js";
    script.async = true;
    script.innerHTML = JSON.stringify(configType);
    document.getElementById(containerId).appendChild(script);
}

function updateDom() {
    const containerId = [
        "chart-xl-1",
        "chart-xl-2",
        "chart-xl-3",
        "chart-xl-4",
      ]
    

  containerId.forEach((el, i) => {
      miniChart(el, config[i+1])
  })
}

// update.watch = () => {
//   // Remove existing widget
//   const widgetContainer = document.getElementById(containerId);
//   while (widgetContainer.firstChild) {
//     widgetContainer.removeChild(widgetContainer.firstChild);
//   }
//   // Load the updated widget
//   updateDom();
// };
updateDom()