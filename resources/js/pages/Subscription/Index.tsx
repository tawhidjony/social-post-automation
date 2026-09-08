import { Form, Head, Link } from '@inertiajs/react';
import SubscriptionController from '@/actions/App/Http/Controllers/SubscriptionController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index as subscriptionIndex, upgrade as subscriptionUpgrade } from '@/routes/subscription';

type Plan = {
    id: number;
    name: string;
    slug: string;
    price: number;
    max_social_accounts: number;
    max_posts_per_month: number;
    max_members: number;
    has_analytics: boolean;
};

type Subscription = {
    id: number;
    status: string;
    starts_at: string | null;
    ends_at: string | null;
    plan: Plan | null;
};

type Usage = {
    social_accounts: number;
    posts_this_month: number;
    members: number;
};

type Props = {
    workspace: { id: number; name: string };
    canManage: boolean;
    currentPlan: Plan | null;
    subscription: Subscription | null;
    usage: Usage;
    plans: Plan[];
};

function formatPrice(price: number): string {
    if (price <= 0) {
        return 'Free';
    }

    return `$${price.toFixed(price % 1 === 0 ? 0 : 2)}/mo`;
}

function UsageRow({
    label,
    used,
    max,
}: {
    label: string;
    used: number;
    max: number;
}) {
    const percent = max > 0 ? Math.min(100, Math.round((used / max) * 100)) : 0;

    return (
        <div className="space-y-1.5">
            <div className="flex items-center justify-between text-sm">
                <span className="text-muted-foreground">{label}</span>
                <span className="font-medium">
                    {used} / {max}
                </span>
            </div>
            <div className="bg-muted h-2 overflow-hidden rounded-full">
                <div
                    className="bg-primary h-full rounded-full transition-all"
                    style={{ width: `${percent}%` }}
                />
            </div>
        </div>
    );
}

export default function SubscriptionIndex({
    workspace,
    canManage,
    currentPlan,
    subscription,
    usage,
}: Props) {
    const isPaid = currentPlan !== null && currentPlan.slug !== 'free';

    return (
        <>
            <Head title="Subscription" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Subscription"
                        description={`Plan and usage for ${workspace.name}`}
                    />

                    <Button variant="outline" asChild>
                        <Link href={subscriptionUpgrade()}>View plans</Link>
                    </Button>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between gap-2">
                                <CardTitle>Current plan</CardTitle>
                                {currentPlan && (
                                    <Badge variant="secondary">
                                        {currentPlan.name}
                                    </Badge>
                                )}
                            </div>
                            <CardDescription>
                                {currentPlan
                                    ? formatPrice(currentPlan.price)
                                    : 'No plan assigned'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {subscription && (
                                <p className="text-muted-foreground text-sm">
                                    Status:{' '}
                                    <span className="text-foreground font-medium capitalize">
                                        {subscription.status}
                                    </span>
                                    {subscription.starts_at && (
                                        <>
                                            {' '}
                                            · Started{' '}
                                            {new Date(
                                                subscription.starts_at,
                                            ).toLocaleDateString()}
                                        </>
                                    )}
                                </p>
                            )}

                            {currentPlan && (
                                <ul className="text-muted-foreground space-y-1 text-sm">
                                    <li>
                                        {currentPlan.max_social_accounts} social
                                        accounts
                                    </li>
                                    <li>
                                        {currentPlan.max_posts_per_month} posts /
                                        month
                                    </li>
                                    <li>
                                        {currentPlan.max_members} workspace
                                        members
                                    </li>
                                    <li>
                                        Analytics:{' '}
                                        {currentPlan.has_analytics
                                            ? 'Included'
                                            : 'Not included'}
                                    </li>
                                </ul>
                            )}

                            {canManage && isPaid && (
                                <Form
                                    {...SubscriptionController.destroy.form()}
                                    options={{ preserveScroll: true }}
                                    className="pt-2"
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            disabled={processing}
                                        >
                                            Cancel &amp; switch to Free
                                        </Button>
                                    )}
                                </Form>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Usage this month</CardTitle>
                            <CardDescription>
                                Limits are based on your current plan. Downgrading
                                does not remove existing resources.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <UsageRow
                                label="Social accounts"
                                used={usage.social_accounts}
                                max={currentPlan?.max_social_accounts ?? 0}
                            />
                            <UsageRow
                                label="Posts this month"
                                used={usage.posts_this_month}
                                max={currentPlan?.max_posts_per_month ?? 0}
                            />
                            <UsageRow
                                label="Members"
                                used={usage.members}
                                max={currentPlan?.max_members ?? 0}
                            />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

SubscriptionIndex.layout = {
    breadcrumbs: [
        {
            title: 'Subscription',
            href: subscriptionIndex(),
        },
    ],
};
