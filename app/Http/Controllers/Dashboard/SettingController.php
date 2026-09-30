<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Activity;
use App\Support\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Business rules the owner can change without a developer.
 */
class SettingController extends Controller
{
    /**
     * Validation per key, so a typo cannot put the business into a nonsense
     * state — a 300% deposit or a zero-hour overtime threshold.
     *
     * @var array<string, array{rule: string, type: string, group: string, label: string, hint: string|null, suffix: string|null}>
     */
    private const SCHEMA = [
        'quote_validity_days' => ['rule' => 'integer|min:1|max:120', 'type' => 'int', 'group' => 'sales', 'label' => 'Quote valid for', 'hint' => 'How long a quote stands before it expires', 'suffix' => 'days'],
        'deposit_percent' => ['rule' => 'integer|min:0|max:100', 'type' => 'int', 'group' => 'sales', 'label' => 'Deposit', 'hint' => 'Asked for when a quote is accepted', 'suffix' => '%'],
        'minimum_guests' => ['rule' => 'integer|min:1|max:500', 'type' => 'int', 'group' => 'sales', 'label' => 'Minimum guests', 'hint' => 'For full catering', 'suffix' => null],
        'chase_quote_after_days' => ['rule' => 'integer|min:1|max:60', 'type' => 'int', 'group' => 'sales', 'label' => 'Chase a quote after', 'hint' => 'Quiet quotes appear on the dashboard', 'suffix' => 'days'],
        'low_stock_lead_days' => ['rule' => 'integer|min:0|max:30', 'type' => 'int', 'group' => 'stock', 'label' => 'Order lead time', 'hint' => 'How far ahead shortages are flagged', 'suffix' => 'days'],
        'waste_alert_percent' => ['rule' => 'integer|min:1|max:100', 'type' => 'int', 'group' => 'stock', 'label' => 'Waste warning above', 'hint' => 'Share of stock value wasted in a month', 'suffix' => '%'],
        'overtime_after_hours' => ['rule' => 'integer|min:1|max:100', 'type' => 'int', 'group' => 'people', 'label' => 'Overtime after', 'hint' => 'Per week, per person', 'suffix' => 'hrs'],
        'late_grace_minutes' => ['rule' => 'integer|min:0|max:120', 'type' => 'int', 'group' => 'people', 'label' => 'Lateness grace', 'hint' => 'Before a shift counts as late', 'suffix' => 'min'],
        'staff_per_guests' => ['rule' => 'integer|min:1|max:200', 'type' => 'int', 'group' => 'people', 'label' => 'One member of staff per', 'hint' => 'Used to suggest staffing on a job', 'suffix' => 'guests'],
        'vat_registered' => ['rule' => 'boolean', 'type' => 'bool', 'group' => 'money', 'label' => 'VAT registered', 'hint' => 'Show VAT on quotes and invoices', 'suffix' => null],
        'vat_percent' => ['rule' => 'integer|min:0|max:100', 'type' => 'int', 'group' => 'money', 'label' => 'VAT rate', 'hint' => null, 'suffix' => '%'],
    ];

    public function __construct(private readonly Activity $activity) {}

    public function index(): View
    {
        $groups = collect(self::SCHEMA)
            ->map(fn (array $meta, string $key) => $meta + ['key' => $key, 'value' => Setting::get($key)])
            ->groupBy('group');

        return view('dashboard.settings.index', [
            'groups' => $groups,
            'menuChoices' => Menu::choices(),
            'menuHidden' => Menu::hidden(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (self::SCHEMA as $key => $meta) {
            $rules[$key] = 'required|'.$meta['rule'];
        }

        $data = $request->validate($rules + [
            'menu_shown' => ['array'],
            'menu_shown.*' => ['string'],
        ]);
        $changed = [];

        // Unticked boxes are not sent, so what is hidden is every choice not ticked.
        if ($request->boolean('menu_present')) {
            $all = array_column(Menu::choices()->flatten(1)->all(), 'route');
            $hidden = array_values(array_diff($all, $data['menu_shown'] ?? []));

            if ($hidden !== Menu::hidden()) {
                $changed['menu_hidden'] = ['from' => implode(', ', Menu::hidden()), 'to' => implode(', ', $hidden)];
                Menu::hide($hidden);
            }
        }

        unset($data['menu_shown']);

        foreach ($data as $key => $value) {
            $meta = self::SCHEMA[$key];
            $before = Setting::get($key);

            if ((string) $before === (string) $value) {
                continue;
            }

            Setting::put($key, $value, $meta['type'], $meta['group']);
            $changed[$key] = ['from' => $before, 'to' => $value];
        }

        if ($changed !== []) {
            $this->activity->log('updated', __('Settings changed'), null, $changed);
        }

        return back()->with('status', $changed === []
            ? __('Nothing changed.')
            : trans_choice('{1}1 setting saved.|[2,*]:count settings saved.', count($changed), ['count' => count($changed)]));
    }
}
