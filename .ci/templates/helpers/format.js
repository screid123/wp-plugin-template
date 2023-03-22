module.exports = (str) => {
	if (typeof str !== 'string') return '';
	return str.replace(/"/g, "'").replace(/(<\/?p>|\n)/g, '');
};
