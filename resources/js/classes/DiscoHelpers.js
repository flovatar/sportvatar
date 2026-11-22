export const colorArray = [
    '#F73034',
    '#4DFF5B',
    '#58BBFF',
    '#FE924C',
    '#D93DFC'
];

export const shuffleArray = (array) => {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
};

export const getRandomColor = () => {
    return colorArray[Math.floor(Math.random() * colorArray.length)]
};

export const getColorBlue = () => {
    return colorArray[2];
}

export const getColorGray = () => {
    return '#aaaaaa';
}
