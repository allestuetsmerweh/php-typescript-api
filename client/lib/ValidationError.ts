export type RequestFieldId<
    Endpoints extends string,
    Requests extends {[key in Endpoints]: any},
> = keyof Requests[Endpoints] & string;

export type ErrorsByField<
    Endpoints extends string,
    Requests extends {[key in Endpoints]: any},
> = {[fieldName in RequestFieldId<Endpoints, Requests>]: string[]};

export class ValidationError<
    Endpoints extends string,
    Requests extends {[key in Endpoints]: any},
> extends Error {
    /* istanbul ignore next */
    constructor(
        message: string,
        private validationErrors: ErrorsByField<Endpoints, Requests>,
    ) {
        super(message); // 'Error' breaks prototype chain here
        Object.setPrototypeOf(this, new.target.prototype); // restore prototype chain
    }

    public getValidationErrors(): ErrorsByField<Endpoints, Requests> {
        return this.validationErrors;
    }
}
