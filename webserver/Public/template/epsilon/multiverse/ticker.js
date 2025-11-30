const containerId = 'tradingview-widget-container';
const symbols = [
  {
    proName: 'FOREXCOM:SPXUSD',
    title: 'S&P 500',
  },
  {
    proName: 'FOREXCOM:NSXUSD',
    title: 'US 100',
  },
  {
    proName: 'FX_IDC:EURUSD',
    title: 'EUR/USD',
  },
  {
    proName: 'BITSTAMP:BTCUSD',
    title: 'Bitcoin',
  },
  {
    proName: 'BITSTAMP:ETHUSD',
    title: 'Ethereum',
  },
];

const loadWidget = () => {
  const config = {
    symbols,
    isTransparent: false,
    showSymbolLogo: true,
    colorTheme: "dark", // Use the value of setTheme
    locale: "en"
  };

  const script = document.createElement('script');
  script.src = 'https://s3.tradingview.com/external-embedding/embed-widget-ticker-tape.js';
  script.async = true;
  script.innerHTML = JSON.stringify(config);
  document.getElementById(containerId).appendChild(script);
};

// Load the widget initially
loadWidget()