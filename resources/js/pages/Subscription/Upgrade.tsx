import { Form, Head, Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import SubscriptionController from '@/actions/App/Http/Controllers/SubscriptionController';
import Heading from '@/components/heading';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
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

type Props = {
    workspace: { id: number; name: string };
    canManage: boolean;
    currentPlan: Plan | null;
    plans: Plan[];
    error?: string | null;
};

function formatPrice(price: number): string {
    if (price <= 0) {
        return '$0';
    }

    return `$${price.toFixed(price % 1 === 0 ? 0 : 2)}`;
}

function planFeatures(plan: Plan): string[] {
    return [
        `${plan.max_social_accounts} social accounts`,
        `${plan.max_posts_per_month} posts per month`,
        `${plan.max_members} workspace member${plan.max_members === 1 ? '' : 's'}`,
        plan.has_analytics ? 'Analytics included' : 'No analytics',
    ];
}

export default function SubscriptionUpgrade({
    workspace,
    canManage,
    currentPlan,
    plans,
    error,
}: Props) {
    return (
        <>
            <Head title="Upgrade plan" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Choose a plan"
                        description={`Upgrade or change the plan for ${workspace.name}`}
                    />

                    <Button variant="ghost" asChild>
                        <Link href={subscriptionIndex()}>Back to subscription</Link>
                    </Button>
                </div>

                {error && (
                    <Alert variant="destructive">
                        <AlertTitle>Limit reached</AlertTitle>
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                )}

                <div className="grid gap-6 md:grid-cols-2 lg:max-w-4xl">
                    {plans.map((plan) => {
                        const isCurrent = currentPlan?.id === plan.id;
                        const features = planFeatures(plan);

                        return (
                            <Card
                                key={plan.id}
                                className={
                                    plan.slug === 'pro'
                                        ? 'border-primary/40'
                                        : undefined
                                }
                            >
                                <CardHeader>
                                    <div className="flex items-center justify-between gap-2">
                                        <CardTitle>{plan.name}</CardTitle>
                                        {isCurrent && (
                                            <Badge variant="secondary">
                                                Current
                                            </Badge>
                                        )}
                                    </div>
                                    <CardDescription>
                                        <span className="text-foreground text-3xl font-semibold tracking-tight">
                                            {formatPrice(plan.price)}
                                        </span>
                                        <span className="text-muted-foreground">
                                            /month
                                        </span>
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ul className="space-y-2 text-sm">
                                        {features.map((feature) => (
                                            <li
                                                key={feature}
                                                className="flex items-start gap-2"
                                            >
                                                <Check className="text-primary mt-0.5 size-4 shrink-0" />
                                                <span>{feature}</span>
                                            </li>
                                        ))}
                                    </ul>
                                </CardContent>
                                <CardFooter>
                                    {isCurrent ? (
                                        <Button
                                            className="w-full"
                                            variant="outline"
                                            disabled
                                        >
                                            Current plan
                                        </Button>
                                    ) : canManage ? (
                                        <Form
                                            {...SubscriptionController.store.form()}
                                            className="w-full"
                                        >
                                            {({ processing }) => (
                                                <>
                                                    <input
                                                        type="hidden"
                                                        name="plan_id"
                                                        value={plan.id}
                                                    />
                                                    <Button
                                                        type="submit"
                                                        className="w-full"
                                                        disabled={processing}
                                                    >
                                                        {currentPlan &&
                                                        plan.price >
                                                            currentPlan.price
                                                            ? `Upgrade to ${plan.name}`
                                                            : `Switch to ${plan.name}`}
                                                    </Button>
                                                </>
                                            )}
                                        </Form>
                                    ) : (
                                        <Button
                                            className="w-full"
                                            variant="outline"
                                            disabled
                                        >
                                            Ask an admin to change plans
                                        </Button>
                                    )}
                                </CardFooter>
                            </Card>
                        );
                    })}
                </div>
            </div>
        </>
    );
}

SubscriptionUpgrade.layout = {
    breadcrumbs: [
        {
            title: 'Subscription',
            href: subscriptionIndex(),
        },
        {
            title: 'Upgrade',
            href: subscriptionUpgrade(),
        },
    ],
};
