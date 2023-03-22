module.exports = (str) => {
	if (typeof str !== 'string') return '';
	return new Date(`${str} GMT-0500`).toISOString();
};
