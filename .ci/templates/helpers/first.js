module.exports = (arr, n) => {
	if (!Array.isArray(arr)) return '';
	if (typeof n !== 'number') return arr[0];
	return arr[n];
};
