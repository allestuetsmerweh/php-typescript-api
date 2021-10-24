import fetch from 'unfetch';

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
            .then((r) => r.json());
    }
}
