module.exports = (str) => {
	if (typeof str !== 'string') return '';
	const date = new Date(`${str} GMT-0500`);
	const options = { year: 'numeric', month: 'long', day: 'numeric' };
	return new Intl.DateTimeFormat('en-US', options).format(date);
};
