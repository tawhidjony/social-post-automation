import React from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { index as workspacesIndex } from '@/routes/workspaces';
import {
    destroy as destroyMember,
    store as storeMember,
    update as updateMember,
} from '@/routes/workspaces/members';

export default function Members({ workspace, members, canManage }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        role: 'editor',
    });

    const handleInvite = (e) => {
        e.preventDefault();
        post(storeMember.url(workspace.id), {
            onSuccess: () => reset('email'),
        });
    };

    const handleRoleChange = (member, role) => {
        router.put(updateMember.url({ workspace: workspace.id, user: member.id }), {
            role,
        });
    };

    const handleRemove = (member) => {
        if (!confirm(`Remove ${member.name} from this workspace?`)) {
            return;
        }

        router.delete(destroyMember.url({ workspace: workspace.id, user: member.id }));
    };

    return (
        <>
            <Head title={`${workspace.name} Members`} />
            <div className="max-w-3xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-6">
                <div>
                    <Link href={workspacesIndex.url()} className="text-sm text-blue-600 hover:underline">
                        ← Back to Workspaces
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 mt-2">
                        Members — {workspace.name}
                    </h1>
                </div>

                {canManage && (
                    <form onSubmit={handleInvite} className="bg-white shadow rounded-lg p-6 space-y-4">
                        <h2 className="font-semibold text-gray-900">Invite member</h2>
                        <p className="text-sm text-gray-500">
                            Invite an existing user by email as admin or editor.
                        </p>
                        <div className="grid gap-4 sm:grid-cols-3">
                            <div className="sm:col-span-2">
                                <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                                    Email
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                {errors.email && (
                                    <p className="mt-1 text-sm text-red-600">{errors.email}</p>
                                )}
                            </div>
                            <div>
                                <label htmlFor="role" className="block text-sm font-medium text-gray-700">
                                    Role
                                </label>
                                <select
                                    id="role"
                                    value={data.role}
                                    onChange={(e) => setData('role', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="editor">Editor</option>
                                    <option value="admin">Admin</option>
                                </select>
                                {errors.role && (
                                    <p className="mt-1 text-sm text-red-600">{errors.role}</p>
                                )}
                            </div>
                        </div>
                        <button
                            type="submit"
                            disabled={processing}
                            className="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition disabled:opacity-50"
                        >
                            {processing ? 'Inviting...' : 'Invite'}
                        </button>
                    </form>
                )}

                <div className="bg-white shadow rounded-lg divide-y divide-gray-200">
                    {members.length === 0 ? (
                        <div className="p-6 text-center text-gray-500">No members.</div>
                    ) : (
                        members.map((member) => (
                            <div
                                key={member.id}
                                className="p-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <p className="font-medium text-gray-800">{member.name}</p>
                                    <p className="text-xs text-gray-400">{member.email}</p>
                                </div>
                                <div className="flex items-center gap-2">
                                    {canManage && member.role !== 'owner' ? (
                                        <select
                                            value={member.role}
                                            onChange={(e) => handleRoleChange(member, e.target.value)}
                                            className="rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        >
                                            <option value="editor">Editor</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    ) : (
                                        <span className="text-xs font-semibold capitalize text-gray-600 px-3 py-1 rounded-full bg-gray-100">
                                            {member.role}
                                        </span>
                                    )}
                                    {canManage && member.role !== 'owner' && (
                                        <button
                                            type="button"
                                            onClick={() => handleRemove(member)}
                                            className="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium bg-red-600 text-white hover:bg-red-700 transition"
                                        >
                                            Remove
                                        </button>
                                    )}
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </div>
        </>
    );
}
