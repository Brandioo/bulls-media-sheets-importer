import React, {
    useCallback,
    useEffect,
    useState,
} from 'react';

import axios from 'axios';

const EMPTY_SOURCE = {
    name: '',
    spreadsheet_id: '',
    active: true,
};

const EMPTY_TAB = {
    sheet_source_id: '',
    name: '',
    header_row: 1,
    active: true,
};

const EMPTY_TARGET = {
    sheet_tab_id: '',
    table_name: '',
    write_mode: 'insert',
    active: true,
};

const EMPTY_MAPPING = {
    import_target_id: '',
    source_column: '',
    target_column: '',
    target_type: 'string',
    nullable: true,
};

export default function App() {
    const [sources, setSources] = useState([]);
    const [runs, setRuns] = useState([]);

    const [sourceForm, setSourceForm] =
        useState(EMPTY_SOURCE);

    const [tabForm, setTabForm] =
        useState(EMPTY_TAB);

    const [targetForm, setTargetForm] =
        useState(EMPTY_TARGET);

    const [mappingForm, setMappingForm] =
        useState(EMPTY_MAPPING);

    const [loading, setLoading] =
        useState(true);

    const [message, setMessage] =
        useState('');

    const [error, setError] =
        useState('');

    const loadConfiguration =
        useCallback(async () => {
            try {
                setLoading(true);

                const response =
                    await axios.get(
                        '/api/configuration'
                    );

                setSources(
                    response.data.sources ?? []
                );

                setRuns(
                    response.data.runs ?? []
                );

                setError('');
            } catch (exception) {
                setError(
                    getErrorMessage(exception)
                );
            } finally {
                setLoading(false);
            }
        }, []);

    useEffect(() => {
        loadConfiguration();
    }, [loadConfiguration]);

    async function createSource(event) {
        event.preventDefault();

        await performAction(async () => {
            await axios.post(
                '/api/sources',
                sourceForm
            );

            setSourceForm(EMPTY_SOURCE);

            return 'Source created successfully.';
        });
    }

    async function createTab(event) {
        event.preventDefault();

        await performAction(async () => {
            await axios.post(
                '/api/tabs',
                {
                    ...tabForm,
                    sheet_source_id:
                        Number(
                            tabForm.sheet_source_id
                        ),
                    header_row:
                        Number(tabForm.header_row),
                }
            );

            setTabForm(EMPTY_TAB);

            return 'Tab created successfully.';
        });
    }

    async function createTarget(event) {
        event.preventDefault();

        await performAction(async () => {
            await axios.post(
                '/api/targets',
                {
                    ...targetForm,
                    sheet_tab_id:
                        Number(
                            targetForm.sheet_tab_id
                        ),
                }
            );

            setTargetForm(EMPTY_TARGET);

            return 'Target created successfully.';
        });
    }

    async function createMapping(event) {
        event.preventDefault();

        await performAction(async () => {
            await axios.post(
                '/api/mappings',
                {
                    ...mappingForm,
                    import_target_id:
                        Number(
                            mappingForm
                                .import_target_id
                        ),
                }
            );

            setMappingForm(EMPTY_MAPPING);

            return 'Mapping created successfully.';
        });
    }

    async function editSource(source) {
        const name = window.prompt(
            'Source name:',
            source.name
        );

        if (name === null) {
            return;
        }

        const spreadsheetId = window.prompt(
            'Spreadsheet ID:',
            source.spreadsheet_id
        );

        if (spreadsheetId === null) {
            return;
        }

        await performAction(async () => {
            await axios.put(
                `/api/sources/${source.id}`,
                {
                    name,
                    spreadsheet_id: spreadsheetId,
                    active: Boolean(source.active),
                }
            );

            return 'Source updated successfully.';
        });
    }

    async function editTab(tab) {
        const name = window.prompt(
            'Tab name:',
            tab.name
        );

        if (name === null) {
            return;
        }

        const headerRow = window.prompt(
            'Header row:',
            String(tab.header_row)
        );

        if (headerRow === null) {
            return;
        }

        await performAction(async () => {
            await axios.put(
                `/api/tabs/${tab.id}`,
                {
                    sheet_source_id:
                        Number(tab.sheet_source_id),
                    name,
                    header_row: Number(headerRow),
                    active: Boolean(tab.active),
                }
            );

            return 'Tab updated successfully.';
        });
    }

    async function editTarget(target) {
        const tableName = window.prompt(
            'Database table:',
            target.table_name
        );

        if (tableName === null) {
            return;
        }

        const writeMode = window.prompt(
            'Write mode: insert, truncate_insert, or upsert',
            target.write_mode
        );

        if (writeMode === null) {
            return;
        }

        if (
            ![
                'insert',
                'truncate_insert',
                'upsert',
            ].includes(writeMode)
        ) {
            setError(
                'Write mode must be insert, truncate_insert, or upsert.'
            );

            return;
        }

        await performAction(async () => {
            await axios.put(
                `/api/targets/${target.id}`,
                {
                    sheet_tab_id:
                        Number(target.sheet_tab_id),
                    table_name: tableName,
                    write_mode: writeMode,
                    unique_by:
                        target.unique_by ?? null,
                    active: Boolean(target.active),
                }
            );

            return 'Target updated successfully.';
        });
    }

    async function editMapping(mapping) {
        const sourceColumn = window.prompt(
            'Google Sheet column:',
            mapping.source_column
        );

        if (sourceColumn === null) {
            return;
        }

        const targetColumn = window.prompt(
            'Database column:',
            mapping.target_column
        );

        if (targetColumn === null) {
            return;
        }

        const targetType = window.prompt(
            'Type: string, text, integer, bigInteger, decimal, boolean, date, datetime, or json',
            mapping.target_type
        );

        if (targetType === null) {
            return;
        }

        const allowedTypes = [
            'string',
            'text',
            'integer',
            'bigInteger',
            'decimal',
            'boolean',
            'date',
            'datetime',
            'json',
        ];

        if (!allowedTypes.includes(targetType)) {
            setError(
                `Invalid type. Use: ${allowedTypes.join(', ')}.`
            );

            return;
        }

        await performAction(async () => {
            await axios.put(
                `/api/mappings/${mapping.id}`,
                {
                    import_target_id:
                        Number(mapping.import_target_id),
                    source_column: sourceColumn,
                    target_column: targetColumn,
                    target_type: targetType,
                    type_options:
                        mapping.type_options ?? null,
                    nullable:
                        Boolean(mapping.nullable),
                    default_value:
                        mapping.default_value ?? null,
                }
            );

            return 'Mapping updated successfully.';
        });
    }

    async function runImport(source) {
        if (
            !window.confirm(
                `Import "${source.name}" now?`
            )
        ) {
            return;
        }

        await performAction(async () => {
            await axios.post(
                `/api/sources/${source.id}/import`
            );

            return `Import completed for ${source.name}.`;
        });
    }

    async function deleteSource(source) {
        if (
            !window.confirm(
                `Delete source "${source.name}"?`
            )
        ) {
            return;
        }

        await performAction(async () => {
            await axios.delete(
                `/api/sources/${source.id}`
            );

            return 'Source deleted.';
        });
    }

    async function deleteTab(tab) {
        if (
            !window.confirm(
                `Delete tab "${tab.name}"?`
            )
        ) {
            return;
        }

        await performAction(async () => {
            await axios.delete(
                `/api/tabs/${tab.id}`
            );

            return 'Tab deleted.';
        });
    }

    async function deleteTarget(target) {
        if (
            !window.confirm(
                `Delete target "${target.table_name}"?`
            )
        ) {
            return;
        }

        await performAction(async () => {
            await axios.delete(
                `/api/targets/${target.id}`
            );

            return 'Target deleted.';
        });
    }

    async function deleteMapping(mapping) {
        if (
            !window.confirm(
                `Delete mapping "${mapping.source_column} → ${mapping.target_column}"?`
            )
        ) {
            return;
        }

        await performAction(async () => {
            await axios.delete(
                `/api/mappings/${mapping.id}`
            );

            return 'Mapping deleted.';
        });
    }

    async function performAction(action) {
        try {
            setError('');
            setMessage('');

            const successMessage =
                await action();

            setMessage(successMessage);

            await loadConfiguration();
        } catch (exception) {
            setError(
                getErrorMessage(exception)
            );
        }
    }

    const tabs = sources.flatMap(
        (source) =>
            (source.tabs ?? []).map(
                (tab) => ({
                    ...tab,
                    source_name: source.name,
                })
            )
    );

    const targets = tabs.flatMap(
        (tab) =>
            (tab.targets ?? []).map(
                (target) => ({
                    ...target,
                    tab_name: tab.name,
                    source_name:
                    tab.source_name,
                })
            )
    );

    return (
        <div className="app-shell">
            <header className="topbar">
                <div>
                    <h1>
                        Google Sheets Importer
                    </h1>

                    <p>
                        Laravel + MySQL automation
                        configuration
                    </p>
                </div>

                <button
                    className="secondary-button"
                    onClick={
                        loadConfiguration
                    }
                >
                    Refresh
                </button>
            </header>

            {message && (
                <div className="alert success">
                    {message}
                </div>
            )}

            {error && (
                <div className="alert error">
                    {error}
                </div>
            )}

            {loading ? (
                <div className="loading">
                    Loading configuration...
                </div>
            ) : (
                <>
                    <section className="stats">
                        <Stat
                            label="Sources"
                            value={
                                sources.length
                            }
                        />

                        <Stat
                            label="Tabs"
                            value={tabs.length}
                        />

                        <Stat
                            label="Targets"
                            value={
                                targets.length
                            }
                        />

                        <Stat
                            label="Imports"
                            value={runs.length}
                        />
                    </section>

                    <section className="grid">
                        <Card title="Add Google Sheet">
                            <form
                                onSubmit={
                                    createSource
                                }
                            >
                                <Input
                                    label="Name"
                                    value={
                                        sourceForm.name
                                    }
                                    onChange={(value) =>
                                        setSourceForm({
                                            ...sourceForm,
                                            name: value,
                                        })
                                    }
                                    required
                                />

                                <Input
                                    label="Spreadsheet ID"
                                    value={
                                        sourceForm
                                            .spreadsheet_id
                                    }
                                    onChange={(value) =>
                                        setSourceForm({
                                            ...sourceForm,
                                            spreadsheet_id:
                                            value,
                                        })
                                    }
                                    required
                                />

                                <SubmitButton>
                                    Add Source
                                </SubmitButton>
                            </form>
                        </Card>

                        <Card title="Add Tab">
                            <form
                                onSubmit={
                                    createTab
                                }
                            >
                                <Select
                                    label="Source"
                                    value={
                                        tabForm
                                            .sheet_source_id
                                    }
                                    onChange={(value) =>
                                        setTabForm({
                                            ...tabForm,
                                            sheet_source_id:
                                            value,
                                        })
                                    }
                                    options={sources.map(
                                        (source) => ({
                                            value:
                                            source.id,
                                            label:
                                            source.name,
                                        })
                                    )}
                                    required
                                />

                                <Input
                                    label="Tab name"
                                    value={
                                        tabForm.name
                                    }
                                    onChange={(value) =>
                                        setTabForm({
                                            ...tabForm,
                                            name: value,
                                        })
                                    }
                                    required
                                />

                                <Input
                                    label="Header row"
                                    type="number"
                                    min="1"
                                    value={
                                        tabForm.header_row
                                    }
                                    onChange={(value) =>
                                        setTabForm({
                                            ...tabForm,
                                            header_row:
                                            value,
                                        })
                                    }
                                    required
                                />

                                <SubmitButton>
                                    Add Tab
                                </SubmitButton>
                            </form>
                        </Card>

                        <Card title="Add Target">
                            <form
                                onSubmit={
                                    createTarget
                                }
                            >
                                <Select
                                    label="Tab"
                                    value={
                                        targetForm
                                            .sheet_tab_id
                                    }
                                    onChange={(value) =>
                                        setTargetForm({
                                            ...targetForm,
                                            sheet_tab_id:
                                            value,
                                        })
                                    }
                                    options={tabs.map(
                                        (tab) => ({
                                            value: tab.id,
                                            label:
                                                `${tab.source_name} / ${tab.name}`,
                                        })
                                    )}
                                    required
                                />

                                <Input
                                    label="Database table"
                                    value={
                                        targetForm
                                            .table_name
                                    }
                                    onChange={(value) =>
                                        setTargetForm({
                                            ...targetForm,
                                            table_name:
                                            value,
                                        })
                                    }
                                    placeholder="sales"
                                    required
                                />

                                <Select
                                    label="Write mode"
                                    value={
                                        targetForm
                                            .write_mode
                                    }
                                    onChange={(value) =>
                                        setTargetForm({
                                            ...targetForm,
                                            write_mode:
                                            value,
                                        })
                                    }
                                    options={[
                                        {
                                            value:
                                                'insert',
                                            label:
                                                'Insert',
                                        },
                                        {
                                            value:
                                                'truncate_insert',
                                            label:
                                                'Replace all',
                                        },
                                        {
                                            value:
                                                'upsert',
                                            label:
                                                'Upsert',
                                        },
                                    ]}
                                />

                                <SubmitButton>
                                    Add Target
                                </SubmitButton>
                            </form>
                        </Card>

                        <Card title="Add Column Mapping">
                            <form
                                onSubmit={
                                    createMapping
                                }
                            >
                                <Select
                                    label="Target"
                                    value={
                                        mappingForm
                                            .import_target_id
                                    }
                                    onChange={(value) =>
                                        setMappingForm({
                                            ...mappingForm,
                                            import_target_id:
                                            value,
                                        })
                                    }
                                    options={targets.map(
                                        (target) => ({
                                            value:
                                            target.id,
                                            label:
                                                `${target.source_name} / ${target.tab_name} → ${target.table_name}`,
                                        })
                                    )}
                                    required
                                />

                                <Input
                                    label="Sheet column"
                                    value={
                                        mappingForm
                                            .source_column
                                    }
                                    onChange={(value) =>
                                        setMappingForm({
                                            ...mappingForm,
                                            source_column:
                                            value,
                                        })
                                    }
                                    placeholder="product_name"
                                    required
                                />

                                <Input
                                    label="Database column"
                                    value={
                                        mappingForm
                                            .target_column
                                    }
                                    onChange={(value) =>
                                        setMappingForm({
                                            ...mappingForm,
                                            target_column:
                                            value,
                                        })
                                    }
                                    placeholder="name"
                                    required
                                />

                                <Select
                                    label="Type"
                                    value={
                                        mappingForm
                                            .target_type
                                    }
                                    onChange={(value) =>
                                        setMappingForm({
                                            ...mappingForm,
                                            target_type:
                                            value,
                                        })
                                    }
                                    options={[
                                        'string',
                                        'text',
                                        'integer',
                                        'bigInteger',
                                        'decimal',
                                        'boolean',
                                        'date',
                                        'datetime',
                                        'json',
                                    ].map(
                                        (value) => ({
                                            value,
                                            label: value,
                                        })
                                    )}
                                />

                                <SubmitButton>
                                    Add Mapping
                                </SubmitButton>
                            </form>
                        </Card>
                    </section>

                    <section>
                        <h2>Configuration</h2>

                        {sources.length === 0 ? (
                            <div className="empty-state">
                                No sources configured.
                            </div>
                        ) : (
                            sources.map(
                                (source) => (
                                    <SourceCard
                                        key={
                                            source.id
                                        }
                                        source={
                                            source
                                        }
                                        onImport={
                                            runImport
                                        }
                                        onEditSource={
                                            editSource
                                        }
                                        onEditTab={
                                            editTab
                                        }
                                        onEditTarget={
                                            editTarget
                                        }
                                        onEditMapping={
                                            editMapping
                                        }
                                        onDeleteSource={
                                            deleteSource
                                        }
                                        onDeleteTab={
                                            deleteTab
                                        }
                                        onDeleteTarget={
                                            deleteTarget
                                        }
                                        onDeleteMapping={
                                            deleteMapping
                                        }
                                    />
                                )
                            )
                        )}
                    </section>

                    <ImportHistory
                        runs={runs}
                    />
                </>
            )}
        </div>
    );
}

function SourceCard({
                        source,
                        onImport,
                        onEditSource,
                        onEditTab,
                        onEditTarget,
                        onEditMapping,
                        onDeleteSource,
                        onDeleteTab,
                        onDeleteTarget,
                        onDeleteMapping,
                    }) {
    return (
        <div className="source-card">
            <div className="source-header">
                <div>
                    <h3>{source.name}</h3>

                    <code>
                        {source.spreadsheet_id}
                    </code>
                </div>

                <div className="actions">
                    <button
                        onClick={() =>
                            onImport(source)
                        }
                    >
                        Run Import
                    </button>

                    <button
                        className="secondary-button"
                        onClick={() =>
                            onEditSource(source)
                        }
                    >
                        Edit
                    </button>

                    <button
                        className="danger-button"
                        onClick={() =>
                            onDeleteSource(
                                source
                            )
                        }
                    >
                        Delete
                    </button>
                </div>
            </div>

            {(source.tabs ?? []).map(
                (tab) => (
                    <div
                        className="tab-block"
                        key={tab.id}
                    >
                        <div className="row-heading">
                            <strong>
                                Tab: {tab.name}
                            </strong>

                            <div className="actions">
                                <button
                                    className="secondary-button"
                                    onClick={() =>
                                        onEditTab(tab)
                                    }
                                >
                                    Edit tab
                                </button>

                                <button
                                    className="icon-danger-button"
                                    onClick={() => onDeleteTab(tab)}
                                    title="Delete tab"
                                    aria-label="Delete tab"
                                >
                                    <TrashIcon/>
                                </button>
                            </div>
                        </div>

                        {(tab.targets ?? []).map(
                            (target) => (
                                <div
                                    className="target-block"
                                    key={
                                        target.id
                                    }
                                >
                                    <div className="row-heading">
                                        <span>
                                            Target:{' '}
                                            <code>
                                                {
                                                    target.table_name
                                                }
                                            </code>
                                            {' · '}
                                            {
                                                target.write_mode
                                            }
                                        </span>

                                        <div className="actions">
                                            <button
                                                className="secondary-button"
                                                onClick={() =>
                                                    onEditTarget(
                                                        target
                                                    )
                                                }
                                            >
                                                Edit target
                                            </button>

                                            <button
                                                className="icon-danger-button"
                                                onClick={() => onDeleteTab(tab)}
                                                title="Delete tab"
                                                aria-label="Delete tab"
                                            >
                                                <TrashIcon/>
                                            </button>
                                        </div>
                                    </div>

                                    <div className="mapping-table">
                                        <div className="mapping-row header">
                                            <span>
                                                Sheet
                                            </span>
                                            <span>
                                                Database
                                            </span>
                                            <span>
                                                Type
                                            </span>
                                            <span/>
                                        </div>

                                        {(
                                            target.mappings ??
                                            []
                                        ).map(
                                            (
                                                mapping
                                            ) => (
                                                <div
                                                    className="mapping-row"
                                                    key={
                                                        mapping.id
                                                    }
                                                >
                                                    <span
                                                        className="mapping-cell"
                                                        data-label="Sheet"
                                                    >
                                                        {mapping.source_column}
                                                    </span>

                                                    <span
                                                        className="mapping-cell"
                                                        data-label="Database"
                                                    >
                                                        {mapping.target_column}
                                                    </span>

                                                    <span
                                                        className="mapping-cell"
                                                        data-label="Type"
                                                    >
                                                        {mapping.target_type}
                                                    </span>

                                                    <div className="mapping-actions">
                                                        <button
                                                            className="edit-button"
                                                            onClick={() => onEditMapping(mapping)}
                                                        >
                                                            Edit
                                                        </button>

                                                        <button
                                                            className="icon-danger-button"
                                                            onClick={() => onDeleteMapping(mapping)}
                                                            title="Delete mapping"
                                                            aria-label="Delete mapping"
                                                        >
                                                            <TrashIcon />
                                                        </button>
                                                    </div>
                                                </div>
                                            )
                                        )}
                                    </div>
                                </div>
                            )
                        )}
                    </div>
                )
            )}
        </div>
    );
}

function ImportHistory({runs}) {
    return (
        <section>
            <h2>Import History</h2>

            {runs.length === 0 ? (
                <div className="empty-state">
                    No imports have been executed yet.
                </div>
            ) : (
                <div className="history-table">
                    <div className="history-row header">
                        <span>Source</span>
                        <span>Status</span>
                        <span>Read</span>
                        <span>Written</span>
                        <span>Started</span>
                    </div>

                    {runs.map((run) => (
                        <div
                            className="history-row"
                            key={run.id}
                        >
                            <span>
                                {run.source
                                        ?.name ??
                                    'Deleted source'}
                            </span>

                            <span>
                                {run.status}
                            </span>

                            <span>
                                {run.rows_read}
                            </span>

                            <span>
                                {
                                    run.rows_written
                                }
                            </span>

                            <span>
                                {run.started_at ??
                                    '-'}
                            </span>
                        </div>
                    ))}
                </div>
            )}
        </section>
    );
}

function Card({title, children}) {
    return (
        <div className="card">
            <h2>{title}</h2>
            {children}
        </div>
    );
}

function Stat({label, value}) {
    return (
        <div className="stat">
            <strong>{value}</strong>
            <span>{label}</span>
        </div>
    );
}

function Input({
                   label,
                   value,
                   onChange,
                   ...props
               }) {
    return (
        <label className="field">
            <span>{label}</span>

            <input
                {...props}
                value={value}
                onChange={(event) =>
                    onChange(
                        event.target.value
                    )
                }
            />
        </label>
    );
}

function Select({
                    label,
                    value,
                    onChange,
                    options,
                    ...props
                }) {
    return (
        <label className="field">
            <span>{label}</span>

            <select
                {...props}
                value={value}
                onChange={(event) =>
                    onChange(
                        event.target.value
                    )
                }
            >
                <option value="">
                    Select...
                </option>

                {options.map(
                    (option) => (
                        <option
                            key={
                                option.value
                            }
                            value={
                                option.value
                            }
                        >
                            {
                                option.label
                            }
                        </option>
                    )
                )}
            </select>
        </label>
    );
}

function SubmitButton({children}) {
    return (
        <button
            className="primary-button"
            type="submit"
        >
            {children}
        </button>
    );
}

function TrashIcon() {
    return (
        <svg
            width="16"
            height="16"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
        >
            <path d="M3 6h18"/>
            <path d="M8 6V4h8v2"/>
            <path d="M19 6l-1 14H6L5 6"/>
            <path d="M10 11v5"/>
            <path d="M14 11v5"/>
        </svg>
    );
}

function getErrorMessage(exception) {
    const errors =
        exception.response?.data?.errors;

    if (errors) {
        return Object.values(errors)
            .flat()
            .join(' ');
    }

    return (
        exception.response?.data?.message ??
        exception.message ??
        'Unexpected error.'
    );
}
