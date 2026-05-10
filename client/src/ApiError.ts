export type ErrorsByField = {[fieldIdent in string]: string[]};

export class ApiError extends Error {
    /* istanbul ignore next */
    constructor(
        public readonly status: number,
        message: string,
        public readonly errorsByField?: ErrorsByField,
    ) {
        super(message); // 'Error' breaks prototype chain here
        Object.setPrototypeOf(this, new.target.prototype); // restore prototype chain
    }
}
