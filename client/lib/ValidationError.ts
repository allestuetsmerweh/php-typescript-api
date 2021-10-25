export type ErrorsByField = {[fieldId in string]: ErrorsByField | string[]};

export class ValidationError extends Error {
    /* istanbul ignore next */
    constructor(
        message: string,
        private errorsByField: ErrorsByField,
    ) {
        super(message); // 'Error' breaks prototype chain here
        Object.setPrototypeOf(this, new.target.prototype); // restore prototype chain
    }

    public getErrorsByField(): ErrorsByField {
        return this.errorsByField;
    }
}
