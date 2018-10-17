/**
 * set cookie
 *
 * @param {String} key
 * @param {String} value
 * @param {Number} day
 * @param {String} path
 */
export function set(key, value='1', day=7, path='/')
{
	let date = new Date();
	date.setTime(date.getTime() + day*24*60*60*1000);
	document.cookie = `${key}=${value};expires=${date.toUTCString()};path=${path}`;
}