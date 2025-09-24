export type ErrorsByField = {[fieldId in string]: Array<ErrorsByField | string>};

export type ErrorsByFlatField = {[fieldId in string]: string[]};

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

    public getErrorsByFlatField(): ErrorsByFlatField {
        const flatMap: ErrorsByFlatField = {};
        this.populateErrorsByFlatField(this.errorsByField, flatMap, '');
        return flatMap;
    }

    protected populateErrorsByFlatField(
        errorsByField: ErrorsByField,
        flatMap: ErrorsByFlatField,
        prefix: string,
    ): void {
        for (const fieldId of Object.keys(errorsByField)) {
            for (const error of errorsByField[fieldId]) {
                if (typeof error === 'string') {
                    const flatFieldId = prefix
                        ? (fieldId === '.' ? prefix : `${prefix}.${fieldId}`)
                        : fieldId;
                    const thisErrors = flatMap[flatFieldId] || [];
                    flatMap[flatFieldId] = [...thisErrors, error];
                } else {
                    const newPrefix = prefix ? `${prefix}.${fieldId}` : fieldId;
                    this.populateErrorsByFlatField(error, flatMap, newPrefix);
                }
            }
        }
    }

    public getErrorsForField(fieldId: string): string[] {
        return this.getErrorsForFieldPath([fieldId]);
    }

    public getErrorsForFieldPath(fieldPath: string[]): string[] {
        const errorsByField = this.getErrorsByFieldForFieldPath(fieldPath);
        return this.getErrorsForErrorsByField(errorsByField);
    }

    public getErrorsByFieldForFieldPath(fieldPath: string[]): ErrorsByField {
        let errorsByField = this.errorsByField;
        for (const pathComponent of fieldPath) {
            const newErrorsByField: ErrorsByField = {};
            for (const error of errorsByField[pathComponent] || []) {
                if (typeof error === 'string') {
                    const thisErrors = newErrorsByField['.'] || [];
                    newErrorsByField['.'] = [...thisErrors, error];
                } else {
                    for (const fieldId of Object.keys(error)) {
                        const errors = error[fieldId];
                        const existingErrors = newErrorsByField[fieldId] || [];
                        newErrorsByField[fieldId] = [
                            ...existingErrors,
                            ...errors,
                        ];
                    }
                }
            }
            errorsByField = newErrorsByField;
        }
        return errorsByField;
    }

    public getErrorsForErrorsByField(errorsByField: ErrorsByField): string[] {
        return Object.keys(errorsByField).flatMap((fieldId) => {
            const prefix = fieldId === '.' ? '' : `Key '${fieldId}': `;
            return errorsByField[fieldId].flatMap((error) => {
                if (typeof error === 'string') {
                    return `${prefix}${error}`;
                }
                return this.getErrorsForErrorsByField(error)
                    .map((flatError) => `${prefix}${flatError}`);
            });
        });
    }
}
