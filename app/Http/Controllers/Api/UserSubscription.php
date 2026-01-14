
<?php



use Illuminate\Database\Eloquent\Model;

class UserSubscription extends Model
{
    protected $guarded = [];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function plan() {
        return $this->belongsTo(VendorSubscriptionPlan::class, 'plan_id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}