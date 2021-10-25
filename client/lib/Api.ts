import fetch from 'unfetch';
import {ErrorsByField, ValidationError} from './ValidationError';

export abstract class Api<
    Endpoints extends string,
    Requests extends {[key in Endpoints]: any},
    Responses extends {[key in Endpoints]: any}
> {
    public abstract baseUrl: string;

    protected fetchFunction = fetch;

    public call<T extends Endpoints>(
        endpoint: T,
        request: Requests[T],
    ): Promise<Responses[T]> {
        const endpointUrl = `${this.baseUrl}/${endpoint}`;
        const fetchFunction = this.fetchFunction;
        return fetchFunction(endpointUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(request),
        })
            .then(async (response) => {
                const responseText = await response.text();
                const error = this.getValidationErrorFromResponseText(responseText);
                if (error) {
                    throw error;
                }
                if (!response.ok && !error) {
                    throw new Error('Ein Fehler ist aufgetreten. Bitte später nochmals versuchen.');
                }
                return response.json() as Responses[T];
            });
    }

    public mergeValidationErrors(
        validationErrors: ValidationError[],
    ): ValidationError {
        const initialValidationErrors = {} as ErrorsByField;
        let merged = new ValidationError('', initialValidationErrors);
        for (const validationError of validationErrors) {
            const newMessage = validationError.message
                ? merged.message + (merged.message ? '\n' : '') + validationError.message
                : merged.message;
            // TODO: Deep merge (concat errors if key present in both dicts)
            const newValidationErrors = {
                ...merged.getErrorsByField(),
                ...validationError.getErrorsByField(),
            };
            merged = new ValidationError(newMessage, newValidationErrors);
        }
        return merged;
    }

    public getValidationErrorFromResponseText(
        responseText?: string,
    ): ValidationError|undefined {
        if (!responseText) {
            return undefined;
        }
        let structuredError;
        try {
            structuredError = JSON.parse(responseText);
        } catch (e: unknown) {
            return undefined;
        }
        if (structuredError?.error?.type !== 'ValidationError') {
            return undefined;
        }
        const message = structuredError.message;
        const validationErrors = structuredError.error.validationErrors;
        if (!message) {
            throw new Error(`Validation error missing message: ${structuredError}`);
        }
        if (!validationErrors) {
            throw new Error(`Validation error missing errors: ${structuredError}`);
        }
        return new ValidationError(message, validationErrors);
    }
}
