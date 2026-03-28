/* eslint-disable no-mixed-operators */
const chunk = ( arr, size ) => {
	// `_v` is unused but required positionally to access `i`.
	return Array.from( { length: Math.ceil( arr.length / size ) }, ( _v, i ) =>
		arr.slice( i * size, i * size + size )
	);
};
export default chunk;
