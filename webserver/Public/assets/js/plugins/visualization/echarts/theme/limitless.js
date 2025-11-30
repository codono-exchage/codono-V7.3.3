define(function() {

var theme10 = {
    // default色板
    color: [
        '#2ec7c9','#b6a2de','#5ab1ef','#ffb980','#d87a80',
        '#8d98b3','#e5cf0d','#97b552','#95706d','#dc69aa',
        '#07a2a4','#9a7fd1','#588dd5','#f5994e','#c05050',
        '#59678c','#c9ab00','#7eb00a','#6f5553','#c14089'
    ],

    // 图表title
    title: {
        textStyle: {
            fontWeight: 'normal',
            fontSize: 17,
            color: '#008acd'          // 主title文字颜色
        }
    },
    
    // 值域
    dataRange: {
        itemWidth: 15,
        color: ['#5ab1ef','#e0ffff']
    },

    // Tools
    toolbox: {
        /*
        color : ['#000000', '#1e90ff', '#1e90ff', '#1e90ff'],
        effectiveColor : '#ff4500',
        feature : {
            mark : {
                title : {
                    mark : 'Markline switch',
                    markUndo : 'Undo markline',
                    markClear : 'Clear markline'
                }
            }
        }
        */
    },


    animationDuration: 1000,

    legend: {
        itemGap: 15
    },

    // Tip box
    tooltip: {
        backgroundColor: 'rgba(0,0,0,0.8)',     // promptbackground color,The default is透明度for0.7of黑色
        padding: [8, 12, 8, 12],
        axisPointer : {            // coordinate轴指示器，coordinate轴触发effective
            type : 'line',         // The default is直线，Optionalfor：'line' | 'shadow'
            lineStyle : {          // 直线指示器styleSet up
                color: '#607D8B',
                width: 1
            },
            crossStyle: {
                color: '#607D8B'
            },
            shadowStyle : {                     // 阴影指示器styleSet up
                color: 'rgba(200,200,200,0.2)'
            }
        },
        textStyle: {
            fontFamily: 'Roboto, sans-serif'
        }
    },

    // 区域缩放Controller
    dataZoom: {
        dataBackgroundColor: '#eceff1',
        fillerColor: 'rgba(96,125,139,0.1)',   // filling颜色
        handleColor: '#607D8B',
        handleSize: 10
    },

    // 网格
    grid: {
        borderColor: '#eee'
    },

    // 类目轴
    categoryAxis: {
        axisLine: {            // coordinate轴线
            lineStyle: {       // AttributeslineStyle控制线条style
                color: '#999',
                width: 1
            }
        },
        splitLine: {           // Separated线
            lineStyle: {       // AttributeslineStyle（详见lineStyle）控制线条style
                color: ['#eee']
            }
        },
        nameTextStyle: {
          fontFamily: 'Roboto, sans-serif'
        },
        axisLabel: {
            textStyle: {
                fontFamily: 'Roboto, sans-serif'
            }
        }
    },

    // 数值型coordinate轴The default parameters
    valueAxis: {
        axisLine: {            // coordinate轴线
            lineStyle: {       // AttributeslineStyle控制线条style
                color: '#999',
                width: 1
            }
        },
        splitArea : {
            show : true,
            areaStyle : {
                color: ['rgba(250,250,250,0.1)','rgba(200,200,200,0.1)']
            }
        },
        splitLine: {           // Separated线
            lineStyle: {       // AttributeslineStyle（详见lineStyle）控制线条style
                color: ['#eee']
            }
        },
        nameTextStyle: {
          fontFamily: 'Roboto, sans-serif'
        },
        axisLabel: {
            textStyle: {
                fontFamily: 'Roboto, sans-serif'
            }
        }
    },

    polar : {
        axisLine: {            // coordinate轴线
            lineStyle: {       // AttributeslineStyle控制线条style
                color: '#ddd'
            }
        },
        splitArea : {
            show : true,
            areaStyle : {
                color: ['rgba(250,250,250,0.2)','rgba(200,200,200,0.2)']
            }
        },
        splitLine : {
            lineStyle : {
                color : '#ddd'
            }
        }
    },

    timeline : {
        lineStyle : {
            color : '#008acd'
        },
        controlStyle : {
            normal : { color : '#008acd'},
            emphasis : { color : '#008acd'}
        },
        symbol : 'emptyCircle',
        symbolSize : 3
    },

    // 柱形图The default parameters
    bar: {
        itemStyle: {
            normal: {
                barBorderRadius: 0
            },
            emphasis: {
                barBorderRadius: 0
            }
        }
    },


    // Pies
    pie: {
        itemStyle: {
            normal: {
                borderWidth: 1,
                borderColor: '#fff'
            },
            emphasis: {
                borderWidth: 1,
                borderColor: '#fff'
            }
        }
    },


    // Default line
    line: {
        smooth : true,
        symbol: 'emptyCircle',  // Symbol type
        symbolSize: 3           // Circle dot size
    },
    
    // K线图The default parameters
    k: {
        itemStyle: {
            normal: {
                color: '#d87a80',       // 阳线filling颜色
                color0: '#2ec7c9',      // 阴线filling颜色
                lineStyle: {
                    color: '#d87a80',   // 阳线frame颜色
                    color0: '#2ec7c9'   // 阴线frame颜色
                }
            }
        }
    },
    
    // 散点图The default parameters
    scatter: {
        symbol: 'circle',    // GraphTypes of
        symbolSize: 4        // Graph大小，半宽（半径）parameter, whenGraphfor方向或菱形则总widthforsymbolSize * 2
    },

    // 雷达图The default parameters
    radar : {
        symbol: 'emptyCircle',    // GraphTypes of
        symbolSize:3
        //symbol: null,         // 拐点GraphTypes of
        //symbolRotate : null,  // Graph旋转控制
    },

    map: {
        itemStyle: {
            normal: {
                areaStyle: {
                    color: '#ddd'
                },
                label: {
                    textStyle: {
                        color: '#d87a80'
                    }
                }
            },
            emphasis: {                 // 也是选中style
                areaStyle: {
                    color: '#fe994e'
                }
            }
        }
    },
    
    force : {
        itemStyle: {
            normal: {
                linkStyle : {
                    color : '#1e90ff'
                }
            }
        }
    },

    chord : {
        itemStyle : {
            normal : {
                borderWidth: 1,
                borderColor: 'rgba(128, 128, 128, 0.5)',
                chordStyle : {
                    lineStyle : {
                        color : 'rgba(128, 128, 128, 0.5)'
                    }
                }
            },
            emphasis : {
                borderWidth: 1,
                borderColor: 'rgba(128, 128, 128, 0.5)',
                chordStyle : {
                    lineStyle : {
                        color : 'rgba(128, 128, 128, 0.5)'
                    }
                }
            }
        }
    },

    gauge : {
        axisLine: {            // coordinate轴线
            lineStyle: {       // AttributeslineStyle控制线条style
                color: [[0.2, '#2ec7c9'],[0.8, '#5ab1ef'],[1, '#d87a80']], 
                width: 10
            }
        },
        axisTick: {            // coordinate轴小标记
            splitNumber: 10,   // 每份split细分How many段
            length :15,        // Attributeslength控制线长
            lineStyle: {       // AttributeslineStyle控制线条style
                color: 'auto'
            }
        },
        splitLine: {           // Separated线
            length :22,         // Attributeslength控制线长
            lineStyle: {       // AttributeslineStyle（详见lineStyle）控制线条style
                color: 'auto'
            }
        },
        pointer : {
            width : 5
        }
    },
    
    textStyle: {
        fontFamily: 'Roboto, Arial, Verdana, sans-serif'
    }
};

    return theme10;
});