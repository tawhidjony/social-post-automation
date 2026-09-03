import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    create,
    destroy,
    edit,
    switchMethod,
} from '@/routes/workspaces';
import { index as membersIndex } from '@/routes/workspaces/members';

const actionButtonClasses =
    'inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium transition';

export default function Index({ workspaces }) {
    const handleSwitch = (workspace) => {
        router.post(switchMethod.url(workspace.id));
    };

    const handleDelete = (workspace) => {
        if (!confirm(`Delete workspace "${workspace.name}"? This cannot be undone.`)) {
            return;
        }

        router.delete(destroy.url(workspace.id));
    };

    return (
        <>
            <Head title="Workspaces" />
            <div className="p-4">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">Workspaces</h1>
                    <Link
                        href={create.url()}
                        className="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition"
                    >
                        + Create Workspace
                    </Link>
                </div>

                <div className="bg-white shadow rounded-lg overflow-hidden">
                    {workspaces.length === 0 ? (
                        <div className="p-6 text-center text-gray-500">
                            No workspaces yet.
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Name
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Role
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Status
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {workspaces.map((workspace) => (
                                        <tr key={workspace.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4">
                                                <p className="text-sm font-medium text-gray-900">
                                                    {workspace.name}
                                                </p>
                                                <p className="text-xs text-gray-400">{workspace.slug}</p>
                                            </td>
                                            <td className="px-6 py-4 text-sm capitalize text-gray-700">
                                                {workspace.role}
                                            </td>
                                            <td className="px-6 py-4">
                                                {workspace.is_current ? (
                                                    <span className="inline-flex px-3 py-1 text-xs rounded-full font-semibold bg-green-100 text-green-700">
                                                        Current
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex px-3 py-1 text-xs rounded-full font-semibold bg-gray-100 text-gray-600">
                                                        Inactive
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-right whitespace-nowrap">
                                                <div className="inline-flex items-center justify-end gap-2 flex-wrap">
                                                    {!workspace.is_current && (
                                                        <button
                                                            type="button"
                                                            onClick={() => handleSwitch(workspace)}
                                                            className={`${actionButtonClasses} bg-blue-600 text-white hover:bg-blue-700`}
                                                        >
                                                            Switch
                                                        </button>
                                                    )}
                                                    <Link
                                                        href={membersIndex.url(workspace.id)}
                                                        className={`${actionButtonClasses} bg-gray-100 text-gray-800 hover:bg-gray-200`}
                                                    >
                                                        Members
                                                    </Link>
                                                    {workspace.can_manage && (
                                                        <Link
                                                            href={edit.url(workspace.id)}
                                                            className={`${actionButtonClasses} bg-gray-100 text-gray-800 hover:bg-gray-200`}
                                                        >
                                                            Edit
                                                        </Link>
                                                    )}
                                                    {workspace.can_delete && (
                                                        <button
                                                            type="button"
                                                            onClick={() => handleDelete(workspace)}
                                                            className={`${actionButtonClasses} bg-red-600 text-white hover:bg-red-700`}
                                                        >
                                                            Delete
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
